# LeanApiBundle

The Leankoala LeanApiBundle is the foundation for using Symfony as restful API.

## Components

- *CORS Listener* - this component handles the mandatory CORS headers. 
These are mandatory if the API should be accessible from a browser. 
- *ApiRequest* - The API request handles the parameters within the HTTP request body. 
It also validates and casts the parameters like the Symfony ParamConverter.

## First steps

The first steps explain how the LeanApiBundle gets installed and how the request data
can be processed.

### Installation

The LeanApiBundle can be installed via composer.

```shell script
$ composer require leankoala/leanapibundle
```

Afterwards add the bundle in the `AppKernel.php`.

```php
$bundles = [
    ...
    new LeankoalaLeanApiBundle(),
    ...
]
```

### API routes

### apiRequest Usage

```php
$apiRequest = new ApiRequest(
    $symfonyRequest,
    $doctrine,
    $schema
);
```
