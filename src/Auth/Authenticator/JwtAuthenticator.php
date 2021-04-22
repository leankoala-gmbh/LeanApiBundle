<?php

namespace Leankoala\LeanApiBundle\Auth\Authenticator;

use Doctrine\Persistence\ManagerRegistry;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Koalamon\IncidentDashboardBundle\Entity\User;
use Leankoala\LeanApiBundle\Auth\Scope\Scope;
use Leankoala\LeanApiBundle\Auth\Scope\ScopeHandler;
use Leankoala\LeanApiBundle\Entity\UserInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class JwtAuthenticator
 *
 * Simple JSON Web Token (JWT) authenticator
 *
 * @see https://jwt.io/
 *
 * @package LeankoalaApi\AuthBundle\Authenticator
 */
class JwtAuthenticator implements Authenticator
{
    const PAYLOAD_KEY_ACCESS = 'access';
    const PAYLOAD_KEY_EXPIRATION = 'exp';
    const PAYLOAD_KEY_TIMESTAMP = 'current_timestamp';
    const PAYLOAD_KEY_TTL = 'ttl';
    const PAYLOAD_KEY_USER_ID = 'user_id';

    /**
     * @var string the token secret
     */
    private $secret;
    private $algorithm;

    private $wasCalled = false;

    private $validToken = true;

    private $scopeAccess = [];

    private $doctrine;

    /**
     * @var ScopeHandler
     */
    private ScopeHandler $scopeHandler;

    /**
     * JwtAuthenticator constructor.
     *
     * @param RegistryInterface $doctrine
     * @param ScopeHandler $scopeHandler
     * @param string $secret
     * @param string $algorithm
     */
    public function __construct(RegistryInterface|ManagerRegistry $doctrine, ScopeHandler $scopeHandler, $secret, $algorithm = 'HS256')
    {
        $this->doctrine = $doctrine;
        $this->scopeHandler = $scopeHandler;
        $this->secret = $secret;
        $this->algorithm = $algorithm;
    }

    /**
     * @inheritDoc
     *
     * @param string|boolean $userToken
     *
     * @throws UnauthorizedException
     */
    public function setUserToken($userToken)
    {
        if ($userToken === false) {
            $this->validToken = false;
            return;
        }

        try {
            $payloadObject = JWT::decode($userToken, $this->secret, [$this->algorithm]);
            $payload = (array)$payloadObject;

            if ($this->hasExpireDate($payload)) {
                $this->scopeAccess = (array)$payload[self::PAYLOAD_KEY_ACCESS];
            } else {
                $this->scopeAccess = $this->getAccessScope($payload);
            }

        } catch (ExpiredException $e) {
            throw new UnauthorizedException('Token expired. Please use the refresh token to authorize again (message: ' . $e->getMessage() . ').');
        } catch (\Exception $e) {
            throw new UnauthorizedException('Token invalid (message: ' . $e->getMessage() . ').');
        }
    }

    /**
     * @inheritDoc
     *
     * This function allows doctrine entities as values in the meta data. The function getId() will
     * be called and the return value is processed.
     *
     * The AuthBundle does not allow controllers to not have an isAllowed request fired. That it
     * why sometimes the isAllowed() method is called without any parameters and will return true in
     * this case. PLEASE be careful with this behaviour.
     *
     * @param string $action
     * @param array $metaData
     * @return bool
     *
     * @todo this function should use the getScope method
     */
    public function isAllowed($action = null, $metaData = []): bool
    {
        $this->wasCalled = true;

        if (is_null($action)) {
            return true;
        }

        if (!$this->validToken) {
            return false;
        }

        if (!$metaData) {
            $metaData = [];
        }

        if (!is_array($metaData)) {
            throw new BadParameterException('When asserting the scope rights the second parameter must be an array of array (action: ' . $action . '.');
        }

        if (array_key_exists($action, $this->scopeAccess)) {
            if (count($metaData) == 0) {
                return true;
            }

            $targets = get_object_vars($this->scopeAccess[$action]);
            foreach ($metaData as $key => $value) {
                if (array_key_exists($key, $targets)) {
                    if (is_object($value) && method_exists($value, 'getId')) {
                        $value = $value->getId();
                    }

                    if (in_array($value, $targets[$key])) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function wasCalled(): bool
    {
        return $this->wasCalled;
    }

    /**
     * Create a JSON web token with the given scope.
     *
     * For better timeout handling the ttl for the access token is also part of the token payload. If
     * $timeToLiveInSeconds is set to 0 there is no expire time set.
     *
     * WARNING: tokens can't be invalidated easily.
     *
     * @param UserInterface $user
     * @param Scope $scope
     * @param integer $timeToLiveInSeconds
     *
     * @return string
     */
    public function createToken(UserInterface $user, Scope $scope, $timeToLiveInSeconds): string
    {
        $payload = [
            self::PAYLOAD_KEY_ACCESS => $scope->toArray(),
            self::PAYLOAD_KEY_TIMESTAMP => time(),
            self::PAYLOAD_KEY_USER_ID => $user->getId()
        ];

        if ($timeToLiveInSeconds != 0) {
            $expirationTime = time() + $timeToLiveInSeconds;
            $payload[self::PAYLOAD_KEY_EXPIRATION] = $expirationTime;
            $payload[self::PAYLOAD_KEY_TTL] = $timeToLiveInSeconds;
        }

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    /**
     * Check if the payload has an expire date
     *
     * @param array $payload
     * @return bool
     */
    private function hasExpireDate($payload): bool
    {
        return array_key_exists(self::PAYLOAD_KEY_EXPIRATION, $payload);
    }

    /**
     * Return the access scope for the given user.
     *
     * @param array $payload
     *
     * @return Scope
     */
    private function getAccessScope($payload): Scope
    {
        if (!array_key_exists(self::PAYLOAD_KEY_USER_ID, $payload)) {
            throw new ForbiddenException('No request parameter with key ' . self::PAYLOAD_KEY_USER_ID . ' found. As the jwt does not provide an expire date this is mandatory.');
        }

        $userId = $payload[self::PAYLOAD_KEY_USER_ID];

        $user = $this->doctrine->getRepository(User::class)->find($userId);

        if (!$user) {
            throw new ForbiddenException('No user with id ' . $userId . ' found.');
        }

        if (!$user->isEnabled()) {
            throw new ForbiddenException('User with id ' . $userId . ' is not enabled yet.');
        }

        return $this->scopeHandler->getScopeByUser($user);
    }

    /**
     * @inheritDoc
     *
     * @return Scope
     */
    public function getScope(): Scope
    {
        return Scope::fromArray($this->scopeAccess);
    }
}
