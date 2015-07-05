<?php

namespace Haigha\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use LinkORB\Component\DatabaseManager\DatabaseManager;
use Nelmio\Alice\Fixtures\Loader as AliceLoader;
use Haigha\TableRecordInstantiator;
use Haigha\Persister\PdoPersister;
use RuntimeException;

class LoadCommand extends Command
{
    protected function configure()
    {
        $this
        ->setName('fixtures:load')
        ->setDescription('Load Alice fixture data into database')
        ->addArgument(
            'filename',
            InputArgument::REQUIRED,
            'Filename'
        )
        ->addArgument(
            'dburl',
            InputArgument::REQUIRED,
            'Database connection details'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $dburl = $input->getArgument('dburl');
        $filename  = $input->getArgument('filename');
        
        $output->write("Haigha: loading [$filename] into [$dburl]\n");
        
        $urlPart = parse_url($dburl);
        if (isset($urlPart['host'])) {
            $dbname = substr($urlPart['path'], 1);
            $dsn = $urlPart['scheme'] . ':dbname=' . $dbname . ';host=' . $urlPart['host'];
            $pdo = new PDO($dsn, $urlPart['user'], $urlPart['pass']);
        } else {
            // not a propper connection url, use database manager
            $dbname = $urlPart['path'];
            $manager = new DatabaseManager();
            $pdo = $manager->getPdo($dbname, 'default');
        }
        
        if (!$pdo) {
            throw new RuntimeException('Invalid database: ' . $dbname);
        }
        
        $locale = 'en_US';
        $seed = 1;
        $providers = array();
        
        $loader = new AliceLoader($locale, $providers, $seed);
        $instantiator = new TableRecordInstantiator();
        //$instantiator->setAutoUuidColumn('r_uuid');
        
        $loader->addInstantiator($instantiator);
        
        $output->write("Loading $filename\n");
        $objects = $loader->load($filename);

        $output->write("Persisting " . count($objects) . " objects in database `$dbname`\n");
        //print_r($objects);
        $persister = new PdoPersister($pdo);
        $persister->persist($objects);
        $output->write("Done\n");
    }
}
