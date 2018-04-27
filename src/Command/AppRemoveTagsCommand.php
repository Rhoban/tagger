<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AppRemoveTagsCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'app:remove-tags';

    protected function configure()
    {
        $this
            ->setDescription('Remove all tags')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $em = $this->getContainer()->get('doctrine')->getManager();

        $em->createQuery('DELETE FROM App:Tag')->execute();

        $em->createQuery('UPDATE App:Patch p
            SET
            p.consensus=false,
            p.votes=0,
            p.votesYes=0,
            p.votesNo=0,
            p.votesUnknown=0,
            p.value=2
        ')->execute();

        $io->success('Removed tags.');
    }
}
