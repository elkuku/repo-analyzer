<?php

namespace App\Command;

use App\Service\Repo;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'analyze',
    description: 'Add a short description for your command',
)]
class AnalyzeCommand extends Command
{
    public function __construct(private readonly string $projectDir)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'root',
                InputArgument::OPTIONAL,
                'Root dir where repositories are located.'
            )
            ->addOption(
                'show-non-repo',
                's',
                InputOption::VALUE_NONE,
                'Show also non repo directories.'
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);
        $showNonRepos = $input->getOption('show-non-repo');
        $rootDir = $input->getArgument('root') ?: dirname($this->projectDir);

        $io->writeln('Repo Base: '.$rootDir);

        $directories = Finder::create()
            ->directories()
            ->in($rootDir)
            ->depth('== 0')
            ->sortByName();

        $table = (new Table($output))
            ->setHeaders(['Repo', 'M', 'A', 'D', 'AM', 'MM', '??']);

        foreach ($directories as $directory) {
            try {
                $repo = new Repo($directory->getRealPath());

                $status = $repo->statusObject();

                $name = sprintf(
                    '<fg=%s>%s</>',
                    ($status ? 'yellow' : 'green'),
                    $directory->getRelativePathname()
                );

                $table->addRow([
                    $name,
                    isset($status['M']) ? count($status['M']) : '',
                    isset($status['A']) ? count($status['A']) : '',
                    isset($status['D']) ? count($status['D']) : '',
                    isset($status['AM']) ? count($status['AM']) : '',
                    isset($status['MM']) ? count($status['MM']) : '',
                    isset($status['??']) ? count($status['??']) : '',
                ]);
            } catch (Exception) {
                if ($showNonRepos) {
                    $table->addRow([
                        sprintf(
                            '<fg=gray>%s</>',
                            $directory->getRelativePathname()
                        ),
                    ]);
                }
            }
        }

        $table->render();

        return Command::SUCCESS;
    }
}
