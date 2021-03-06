<?php

namespace Leankoala\LeanApiBundle\Command;

use Leankoala\LeanApiBundle\Client\Creator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CreateClientCommand
 *
 * This command is used to create clients in different languages.
 *
 * @package Leankoala\LeanApiBundle\Command
 *
 * @author Nils Langner (nils.langner@leankoala.com)
 * @created 2020-07-08
 */
class CreateClientCommand extends ContainerAwareCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('lean:api:client:create')
            ->setDescription('Create a client for a given language')
            ->addArgument(
                'language',
                InputArgument::REQUIRED,
                'The output language')
            ->addArgument(
                'dir',
                InputArgument::REQUIRED,
                'The output directory'
            )->addOption(
                'pathPrefix',
                'p',
                InputOption::VALUE_OPTIONAL,
                'The path prefix for API routes',
                '/kapi'
            )->addOption(
                'removePrefix',
                'r',
                InputOption::VALUE_OPTIONAL,
                'The path prefix for API routes',
                false
            );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $language = $input->getArgument('language');
        $outputDir = $input->getArgument('dir');
        $pathPrefix = $input->getOption('pathPrefix');
        $removePrefix = $input->getOption('removePrefix');

        $output->writeln('');
        $output->writeln('  Creating client for <info>' . $language . '</info> with path prefix <info>' . $pathPrefix . '</info>.');
        $output->writeln('');

        $creator = new Creator($this->getContainer()->get('router'), $this->getContainer()->get('twig'), $outputDir, $removePrefix);
        $newFiles = $creator->create($language, $pathPrefix);

        $output->writeln('  <info>Successfully</info> created ' . count($newFiles) . ' files:');
        $output->writeln('');

        foreach ($newFiles as $newFile) {
            $output->writeln('    - ' . $newFile);
        }

        $output->writeln('');
    }
}
