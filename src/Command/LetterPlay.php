<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\String\UnicodeString;

class LetterPlay extends Command
{
    const OPTION_INPUT = 'input';
    const OPTION_FORMAT = 'format';
    const OPTION_INCLUDE_LETTER = 'include-letter';
    const OPTION_INCLUDE_PUNCTUATION = 'include-punctuation';
    const OPTION_INCLUDE_SYMBOL = 'include-symbol';

    const FORMAT_NON_REPEATING = 'non-repeating';
    const FORMAT_LEAST_REPEATING = 'least-repeating';
    const FORMAT_MOST_REPEATING = 'most-repeating';

    const ALLOWED_FORMATS = [self::FORMAT_NON_REPEATING, self::FORMAT_LEAST_REPEATING, self::FORMAT_MOST_REPEATING];

    /**
     * @var null
     */
    private $fileContent = null;
    /**
     * @var null
     */
    private $format = null;
    /**
     * @var array
     */
    private $includeFlags = [];

    protected function configure(): void
    {
        $this
            ->setName('letter play')
            ->addOption(self::OPTION_INPUT,'i',InputOption::VALUE_REQUIRED)
            ->addOption(self::OPTION_FORMAT,'f',InputOption::VALUE_REQUIRED)
            ->addOption(self::OPTION_INCLUDE_LETTER,'L',InputOption::VALUE_NONE)
            ->addOption(self::OPTION_INCLUDE_PUNCTUATION,'P',InputOption::VALUE_NONE)
            ->addOption(self::OPTION_INCLUDE_SYMBOL,'S',InputOption::VALUE_NONE)
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output) : int
    {
        $start = microtime(true);
        try {
            $this->checkInputOptionConditions($input->getOption(self::OPTION_INPUT), $output);
            $this->checkFormatOptionConditions($input->getOption(self::OPTION_FORMAT), $output);
            $this->checkFlagOptionConditions(
                $input->getOption(self::OPTION_INCLUDE_LETTER),
                $input->getOption(self::OPTION_INCLUDE_PUNCTUATION),
                $input->getOption(self::OPTION_INCLUDE_SYMBOL),
                $output
            );
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
            $output->writeln("Error code " . $e->getCode());
            return $e->getCode();
        }
        $output->writeln('Processing check');

        $this->processContent($output);

        $doneIn = (microtime(true) - $start);
        $output->writeln('Finished in: '. sprintf('%.6f', $doneIn));
        return Command::SUCCESS;
    }

    /**
     * @param $inputOption
     * @param OutputInterface $output
     * @throws \InvalidArgumentException
     */
    private function checkInputOptionConditions($inputOption, OutputInterface $output): void
    {
        //if i/input is provided but value is not valid path to file or value not provided exit with code 1
        if ($inputOption === null) {
            throw new \InvalidArgumentException("Option " . self::OPTION_INPUT . ' not passed', 1);
        }

        $output->writeln("File: " . $inputOption);
        if (!$this->checkFileExists($inputOption)) {
            throw new \InvalidArgumentException("Option " . self::OPTION_INPUT . ' does not resolve to a file', 1);
        }

        //if i/input contents are lower case alphabet ASCII letters, punctuations and symbols only, otherwise exit with code 2
        $this->getFileContent($inputOption);
        if (!$this->checkFileContent()) {
            throw new \InvalidArgumentException("Option " . self::OPTION_INPUT . ' resolves to a file with invalid content', 2);
        }
        $output->writeln(self::OPTION_INPUT . ' option validated');
    }

    /**
     * @param string $path
     * @return bool
     */
    private function checkFileExists(string $path): bool
    {
        $filesystem = new Filesystem();
        return $filesystem->exists($path);
    }

    /**
     * @param string $path
     */
    private function getFileContent(string $path): void
    {
        $file = new SplFileInfo($path, '','');
        $this->fileContent = $file->getContents();
    }

    /**
     * @return bool
     */
    private function checkFileContent(): bool
    {
        $matches = [];
        $res = preg_match('/([[:lower:]]*[[:punct:]]*)*/', $this->fileContent, $matches);
        return $res && (isset($matches[0]) && $matches[0] === $this->fileContent);
    }

    /**
     * @param $formatOption
     * @param OutputInterface $output
     * @throws \InvalidArgumentException
     */
    private function checkFormatOptionConditions($formatOption, OutputInterface $output): void
    {
        // if f/format option is not provided or not one of $allowedFormats exit with code 3
        if ($formatOption === null) {
            throw new \InvalidArgumentException("Option " . self::OPTION_FORMAT . ' not passed', 3);
        }

        if (!in_array($formatOption, self::ALLOWED_FORMATS)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Option %s passed, but %s is not in allowed list: %s',
                    self::OPTION_FORMAT,
                    $formatOption,
                    implode(', ', self::ALLOWED_FORMATS)
                ),
                3
            );
        }
        $this->format = $formatOption;
        $output->writeln(self::OPTION_FORMAT . ' option validated');
    }

    /**
     * @param $letterOption
     * @param $punctuationOption
     * @param $symbolOption
     * @param OutputInterface $output
     * @throws \InvalidArgumentException
     */
    private function checkFlagOptionConditions($letterOption, $punctuationOption, $symbolOption, OutputInterface $output): void
    {
        //if none of the L/P/S is provided exit with code 4
        if ($letterOption) {
            $this->includeFlags[] = self::OPTION_INCLUDE_LETTER;
        }

        if ($punctuationOption) {
            $this->includeFlags[] = self::OPTION_INCLUDE_PUNCTUATION;
        }

        if ($symbolOption) {
            $this->includeFlags[] = self::OPTION_INCLUDE_SYMBOL;
        }

        if (count($this->includeFlags) === 0) {
            throw new \InvalidArgumentException('At least one of the L/P/S flags must be passed', 4);
        }

        $output->writeln(sprintf('%s flags set successfully', implode(', ', $this->includeFlags)));
    }

    /**
     * @param OutputInterface $output
     */
    private function processContent(OutputInterface $output)
    {
        $characterCountData = count_chars($this->fileContent, 1);

        // lowercase letter codes
        $letterCodes = range(97, 122);
        // https://grammar.yourdictionary.com/punctuation/what/fourteen-punctuation-marks.html
        $punctuationCodes = [
            33, 34, 38, 44, 46, 63, 58, 59, 45, 91, 93, 123, 125, 40, 41, 39, 96,
            95, 42, 35, 37
            ];
        // lets imagine that everything left after regexp validation and letters/punctuation extraction are symbols
        $symbolCodes = array_keys(array_diff(range(0, 255), $letterCodes, $punctuationCodes));

        $charCodes = [
            self::OPTION_INCLUDE_LETTER => $letterCodes,
            self::OPTION_INCLUDE_PUNCTUATION => $punctuationCodes,
            self::OPTION_INCLUDE_SYMBOL => $symbolCodes,
        ];

        foreach($this->includeFlags as $flag) {
            $flaggedCharacterSetCountData = array_filter($characterCountData, function ($key) use ($charCodes, $flag) {
                return in_array($key, $charCodes[$flag]);
            }, ARRAY_FILTER_USE_KEY);

            switch ($this->format) {
                case self::FORMAT_NON_REPEATING:
                    $this->findFormatSolution(1, $characterCountData, $charCodes[$flag], $flag, $output);
                    break;

                case self::FORMAT_MOST_REPEATING:
                    $this->findFormatSolution(max($flaggedCharacterSetCountData), $characterCountData, $charCodes[$flag], $flag, $output);
                    break;

                case self::FORMAT_LEAST_REPEATING:
                    $targetValue =  min(array_filter($flaggedCharacterSetCountData, function($var) { return $var > 1;}));
                    $this->findFormatSolution($targetValue, $characterCountData, $charCodes[$flag], $flag, $output);
                    break;
            }
        }
    }

    /**
     * @param $target int
     * @param $charData []
     * @param $charCodes []
     * @param $flag string
     * @param OutputInterface $output
     */
    private function findFormatSolution(int $target, $charData, $charCodes, string $flag, OutputInterface $output): void
    {
        $firstLetter = -1;
        for ($i = 0; $i < strlen($this->fileContent); $i++) {
            $stringCursorValue = ord($this->fileContent[$i]);
            if ($charData[$stringCursorValue] == $target && in_array($stringCursorValue, $charCodes)) {
                $firstLetter = $i;
                break;
            }
        }
        $output->writeln(sprintf(
            'First %s %s: %s',
            $this->format,
            explode('-', $flag)[1],
            $firstLetter ? $this->fileContent[$firstLetter] : 'None'
        ));
    }
}