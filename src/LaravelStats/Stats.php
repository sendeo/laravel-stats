<?php

namespace Sendeo\LaravelStats;

use Symfony\Component\Finder\Finder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use SebastianBergmann\PHPLOC\Analyser;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableStyle;

class Stats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rake-style code stats.';

    /**
     * Create a new command instance.
     *
     * @param Analyser $analyser
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $content = [];

        $folderMapping = [
            'Controllers' => app_path('Http/Controllers'),
            'Models' => app_path('Models'),
            'Jobs' => app_path('Jobs'),
            'Listeners' => app_path('Listeners'),
            'Mailables' => app_path('Mail'),
            'Policies' => app_path('Policies'),
            'Requests' => app_path('Http/Requests'),
            'Resources' => app_path('Http/Resources'),
            'Feature Tests' => base_path('tests/Feature'),
            'Unit Tests' => base_path('tests/Unit'),
        ];

        collect($folderMapping)->each(function ($folder, $type) use (&$content) {
            $data = $this->analyze($folder);

            if (!$data) {
                return;
            }

            $classes = $data['classes'] + $data['testClasses'];
            $methods = $data['methods'] + $data['testMethods'];

            $content[] = [
                $type,
                $data['loc'],
                $data['lloc'],
                $classes,
                $methods,
                round($classes ? $methods / $classes : 0),
                round($methods ? $data['lloc'] / $methods : 0),
            ];
        });

        $codeTotals = $this->analyze(app_path());
        $testTotals = $this->analyze(base_path('tests'));

        $codeTotalLloc = $codeTotals['lloc'];
        $testTotalLloc = $testTotals['lloc'];

        if ($codeTotals['lloc'] > $testTotals['lloc']) {
            $codeToTest = $testTotalLloc ? round($codeTotalLloc / $testTotalLloc, 1) . ':1' : '∞:1';
        } else {
            $codeToTest = $codeTotalLloc ? '1:' . round($testTotalLloc / $codeTotalLloc, 1) : '1:∞';
        }

        $content[] = new TableSeparator;
        $content[] = [
            'Total',
            $codeTotals['loc'] + $testTotals['loc'],
            $codeTotals['lloc'] + $testTotals['lloc'],
            $codeTotals['classes'] + $testTotals['classes'],
            $codeTotals['methods'] + $testTotals['methods'],
            round(($codeTotals['classes'] + $testTotals['classes']) ? ($codeTotals['methods'] + $testTotals['methods']) / ($codeTotals['classes'] + $testTotals['classes']) : 0),
            round(($codeTotals['methods'] + $testTotals['methods']) ? ($codeTotals['lloc'] + $testTotals['lloc']) / ($codeTotals['methods'] + $testTotals['methods']) : 0),
        ];

        $headers = ['Name', 'Lines', 'LOC', 'Classes', 'Methods', 'M/C', 'LOC/M'];

        $rightAlign = new TableStyle();
        $rightAlign->setPadType(STR_PAD_LEFT);

        $table = new Table($this->getOutput());
        $table->setColumnStyle(1, $rightAlign)
            ->setColumnStyle(2, $rightAlign)
            ->setColumnStyle(3, $rightAlign)
            ->setColumnStyle(4, $rightAlign)
            ->setColumnStyle(5, $rightAlign)
            ->setColumnStyle(6, $rightAlign)
            ->setHeaders($headers)
            ->setRows($content)
            ->render();
        $this->info("  Code LOC: {$codeTotals['lloc']}     Test LOC: {$testTotals['lloc']}     Code to Test Ratio: " . $codeToTest);
    }

    /**
     * Analyzes given files.
     * We need to create a new instance every time because phploc
     * doesn't clear the old files when countFiles() is run.
     *
     * @param $files
     * @param $countTests
     * @return array
     */
    private function analyze($folder)
    {
        if (!is_dir($folder)) {
            return;
        }

        $analyser = new Analyser;

        $files = [];

        $finder = new Finder();
        $filesIterator = $finder->files()->in($folder);
        foreach ($filesIterator as $file) {
            $files[] = $folder . '/' . $file->getRelativePathname();
        }

        return $analyser->countFiles($files, true);
    }
}
