<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Repository\PatchRepository;
use App\Entity\Patch;

class AppConsensusCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'app:consensus';

    protected function configure()
    {
        $this
            ->setDescription('Recompute the consensus')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $em = $this->getContainer()->get('doctrine')->getManager();
        $patches = $em->getRepository(Patch::class);

        $everything = $em
            ->createQuery('SELECT p
                FROM App:Patch p
                LEFT JOIN p.tags tags
                ')
            ->getResult();

        $count = 0;
        foreach ($everything as $patch) {
            $count++;
            $patch->recompute();
            if ($count%10000 == 0) {
                $io->success("$count...");
                $em->flush();
            }
        }
        $em->flush();

        $io->success("Re-computed consensus for $count patches.");
    }
}
