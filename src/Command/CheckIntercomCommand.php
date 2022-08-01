<?php

// src/Command/CreateUserCommand.php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpClient\HttpClient;

// the name of the command is what users type after "php bin/console"
#[AsCommand(name: 'app:check-intercom')]
class CheckIntercomCommand extends Command
{
    protected static $defaultName = 'app:check-intercom';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $reboot = false;
        $httpClient = HttpClient::create();
        try {
            $response = $httpClient->request('GET',  $_ENV['INTERCOM_ENDPOINT'], ['timeout' => 3]);

            $statusCode = $response->getStatusCode();

            if ($statusCode != 200) {
                $reboot = true;
            } else {
                $output->writeln('<info>Intercom is working fine</info>');
            }
        } catch(\Exception $e) {
            $reboot = true;
        }

        if($reboot) {
            $output->writeln('<info>Need to reboot</info>');
            try {
                $httpClient->request('GET',  $_ENV['TURN_OFF_ENDPOINT'], ['timeout' => 3]);
            } catch (\Exception $e) {
                $output->writeln('<error>Turning off failure: '.$e->getMessage().'</error>');
            }
            $output->writeln('Sleep for a while');
            for($i = 0; $i < 15; $i++) {
                sleep(1);
                $output->write('.');
            }
            $output->writeln('');

            try {
                $httpClient->request('GET', $_ENV['TURN_ON_ENDPOINT'], ['timeout' => 3]);
            } catch (\Exception $e) {
                $output->writeln('<error>Turning on failure: '.$e->getMessage().'</error>');
            }
        }

        return Command::SUCCESS;
    }
}