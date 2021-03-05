<?php


namespace Jimixjay\SemverRecommendation;

use Exception;
use Illuminate\Console\Command;
use Jimixjay\Exceptions\FailExec;
use Jimixjay\Exceptions\IncorrectVersionFormat;

class SemverRecommendation extends Command
{
    const LEVELS = [
        'PATCH',
        'MINOR',
        'MAJOR',
    ];
    protected $signature = "semver:recommendation";
    protected $description = "Calculate the version for next release based on master";
    private $lastVersion;
    private $currentBranch;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        try {
            $stashApplied = false;
            $lastVersion =
                $this->ask('Introduce the code version to compare: ', $this->composerAppVersion());
            $this->lastVersion = trim($lastVersion);

            $this->assertLastVersionHasCorrectFormat();

            $this->setCurrentBranch();

            $this->applyStash();
            $stashApplied = true;

            $this->pullMaster();

            $this->createTempFolder();

            $this->cloneLastVersion();

            $result = shell_exec('php vendor/bin/php-semver-checker compare temp-repository/app app');
            $level  = substr($result, strpos($result, 'Suggested semantic versioning change: ') + 38, 5);

            $msg = 'No changes detected, so you don\'t need to change your code version' . $level;
            if (in_array($level, self::LEVELS)) {
                $recommendedVersion = $this->calculateRecommendedVersion($level);
                $msg                = 'Recommended version ' . $recommendedVersion;
            }

            $this->info($msg);

            $this->deleteTempFolder();

            $this->revertBranchChanges();

        } catch (Exception $e) {
            $this->deleteTempFolder();

            exec('git branch --show-current', $branch);
            $actualBranch = $branch[0];

            if (isset($this->currentBranch) && $this->currentBranch != $actualBranch) {
                exec('git checkout -q ' . $this->currentBranch);
            }

            if ($stashApplied && (isset($this->currentBranch) && $this->currentBranch != 'master')) {
                passthru('git stash apply', $response);
                $this->assertResponseWasCorrect($response,
                                                'Fail applying "git stash". Please try it manually to preserve your previous changes', 502);
            }

            $this->error('Error ' . $e->getCode() . ': ' . $e->getMessage());
        }
    }

    private function assertLastVersionHasCorrectFormat()
    {
        $versionExploded = explode('.', $this->lastVersion);

        if (count($versionExploded) != 3) {
            throw new IncorrectVersionFormat($this->lastVersion);
        }
    }

    private function setCurrentBranch()
    : void
    {
        exec('git branch --show-current', $branch);

        $this->currentBranch = $branch[0];
    }

    private function applyStash()
    : void
    {
        passthru('git stash -q', $response);
        $this->assertResponseWasCorrect($response, 'Fail doing "git stash"', 503);
    }

    private function assertResponseWasCorrect($response, $msg, $code)
    {
        if ($response != 0) {
            throw new FailExec($msg, $code);
        }
    }

    private function pullMaster()
    : void
    {
        passthru('git checkout master -q', $response);
        $this->assertResponseWasCorrect($response, 'Fail changing to "master" branch', 504);

        passthru('git pull origin master -q', $response);
        $this->assertResponseWasCorrect($response, 'Fail pulling "master"', 505);
    }

    private function createTempFolder()
    : void
    {
        passthru('mkdir temp-repository', $response);
        $this->assertResponseWasCorrect($response, 'Fail creating the temp folder', 506);
    }

    private function cloneLastVersion()
    : void
    {
        exec('git config --get remote.origin.url', $remoteUrlRepo);
        $remoteUrlRepo = $remoteUrlRepo[0];

        passthru('git clone -q -b "release/v' . $this->lastVersion . '" ' . $remoteUrlRepo . ' temp-repository',
                 $response);
        $this->assertResponseWasCorrect($response,
                                        'The remote branch release/v' . $this->lastVersion . ' doesn\'t exists', 507);
    }

    private function calculateRecommendedVersion(string $level)
    : string
    {
        $versionExploded = explode('.', $this->lastVersion);
        $majorVersion    = $versionExploded[0];
        $minorVersion    = $versionExploded[1];
        $patchVersion    = $versionExploded[2];

        switch ($level) {
            case 'PATCH':
                $recommendedVersion = $majorVersion . '.' . $minorVersion . '.' . ($patchVersion + 1);
                break;
            case 'MINOR':
                $recommendedVersion = $majorVersion . '.' . ($minorVersion + 1) . '.0';
                break;
            case 'MAJOR':
                $recommendedVersion = ($majorVersion + 1) . '.0.0';
                break;
            default:
                $recommendedVersion = $this->lastVersion;
        }

        return $recommendedVersion;
    }

    private function deleteTempFolder()
    : void
    {
        passthru('rm -rf temp-repository', $response);
        $this->assertResponseWasCorrect($response, 'Fail deleting temp folder. Please try to delete manually', 508);
    }

    private function revertBranchChanges()
    : void
    {
        exec('git checkout -q ' . $this->currentBranch);

        if ($this->currentBranch != 'master') {
            passthru('git stash apply', $response);
            $this->assertResponseWasCorrect($response,
                                            'Fail applying "git stash". Please try it manually to preserve your previous changes', 509);
        }
    }

    function composerAppVersion()
    {
        $composer = json_decode(file_get_contents('composer.json'), true);

        return $composer['version'] ?? '';
    }

}
