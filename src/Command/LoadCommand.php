<?php

namespace Haigha\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use LinkORB\Component\DatabaseManager\DatabaseManager;
use Nelmio\Alice\Fixtures\Loader as AliceLoader;
use Haigha\TableRecordInstantiator;
use Haigha\Persister\PdoPersister;
use Haigha\Exception\FileNotFoundException;
use Haigha\Exception\InvalidDatabaseException;
use RuntimeException;
use PDO;

class LoadCommand extends Command
{
    /**
     * {@inheritdoc}
     */
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
            )
            ->addArgument(
                'autouuidfield',
                InputArgument::OPTIONAL,
                'Fieldname for automatically generating uuids on all records'
            )
            ->addOption(
                'locale',
                'l',
                InputOption::VALUE_REQUIRED,
                'Locale for Alice',
                'en_US'
            )
            ->addOption(
                'seed',
                null,
                InputOption::VALUE_REQUIRED,
                'Seed for Alice',
                1
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $dburl = $input->getArgument('dburl');
        $filename  = $input->getArgument('filename');
        $autoUuidField  = $input->getArgument('autouuidfield');
        $locale = $input->getOption('locale');
        $seed = $input->getOption('seed');

        if (!file_exists($filename)) {
            throw new FileNotFoundException($filename);
        }

        $manager = new DatabaseManager();
        $pdo = $manager->getPdo($dburl, 'default');

        $providers = array();
        $loader = new AliceLoader($locale, $providers, $seed);

        $instantiator = new TableRecordInstantiator();
        if ($autoUuidField) {
            $instantiator->setAutoUuidColumn($autoUuidField);
        }
        $loader->addInstantiator($instantiator);

        $output->writeln(sprintf(
            "Loading '%s' into %s",
            $filename,
            $dburl
        ));
        $objects = $loader->load($filename);

        $output->writeln(sprintf(
            "Persisting '%s' objects in database '%s'",
            count($objects),
            $dburl
        ));

        $persister = new PdoPersister($pdo);
        $persister->persist($objects);

        $output->writeln("Done");
    }
}
