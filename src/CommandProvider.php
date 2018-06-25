<?php

namespace Cleaner\Composer;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\Command\BaseCommand;
use Cleaner\Composer\VendorCleanerPlugin;

class CommandProvider implements CommandProviderCapability
{
    public function getCommands()
    {
        return array(new Command);
    }
}

class Command extends BaseCommand
{
    protected function configure()
    {
        $this->setName('clean-vendor');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Clean Vendor Directory...');

        $composer = $this->getComposer();
        $io = $this->getIO();

        $cleaner = new VendorCleanerPlugin;
        $cleaner->activate($composer, $io);
        $cleaner->clean(false);
    }
}