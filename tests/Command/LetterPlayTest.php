<?php

use PHPUnit\Framework\TestCase;
use App\Command\LetterPlay;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Application;

class LetterPlayTest extends TestCase
{
    public function testExecutionFailsWithoutParameters(): void
    {
        $application = new Application('echo', '1.0.0');
        $command = new LetterPlay();

        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);
        $this->assertStringContainsString('Option input not passed', $commandTester->getDisplay(true));
    }

    public function testExecutionFailsWithWrongInputParameters(): void
    {
        $application = new Application('echo', '1.0.0');
        $command = new LetterPlay();

        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--input' => 'wrong/path',
        ]);
        $result = $commandTester->getDisplay(true);
        $this->assertStringContainsString('Option input does not resolve to a file', $result);
        $this->assertStringContainsString('Error code 1', $result);
    }

    public function testExecutionFailsWithWrongFileContentInputParameters(): void
    {
        $application = new Application('echo', '1.0.0');
        $command = new LetterPlay();

        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--input' => 'public/invalid-input.txt',
        ]);
        $result = $commandTester->getDisplay(true);
        $this->assertStringContainsString('Option input resolves to a file with invalid content', $result);
        $this->assertStringContainsString('Error code 2', $result);
    }

    public function testExecutionFailsWithWrongFormatParameters(): void
    {
        $application = new Application('echo', '1.0.0');
        $command = new LetterPlay();

        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--input' => 'public/valid-input.txt',
            '--format' => 'wrong-format',
        ]);
        $result = $commandTester->getDisplay(true);
        $this->assertStringContainsString('input option validated', $result);
        $this->assertStringContainsString('is not in allowed list:', $result);
        $this->assertStringContainsString('Error code 3', $result);
    }

    public function testExecutionFailsWithAbsentConditionParameters(): void
    {
        $application = new Application('echo', '1.0.0');
        $command = new LetterPlay();

        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--input' => 'public/valid-input.txt',
            '--format' => 'non-repeating',
        ]);
        $result = $commandTester->getDisplay(true);
        $this->assertStringContainsString('input option validated', $result);
        $this->assertStringContainsString('format option validated', $result);
        $this->assertStringContainsString('At least one of the L/P/S flags must be passed', $result);
        $this->assertStringContainsString('Error code 4', $result);
    }

    public function testExecutionSucceedsWithCorrectParameters(): void
    {
        $application = new Application('echo', '1.0.0');
        $command = new LetterPlay();

        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--input' => 'public/valid-input.txt',
            '--format' => 'non-repeating',
            '--include-letter' => true,
        ]);
        $result = $commandTester->getDisplay(true);
        $this->assertStringContainsString('input option validated', $result);
        $this->assertStringContainsString('format option validated', $result);
        $this->assertStringContainsString('flags set successfully', $result);
        $this->assertStringContainsString('Processing check', $result);
    }

    public function testNonRepeatingLetterFromValidFileFoundSuccessfully(): void
    {
        $application = new Application('echo', '1.0.0');
        $command = new LetterPlay();

        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--input' => 'public/valid-input.txt',
            '--format' => 'non-repeating',
            '--include-letter' => true,
        ]);
        $result = $commandTester->getDisplay(true);
        $this->assertStringContainsString('input option validated', $result);
        $this->assertStringContainsString('format option validated', $result);
        $this->assertStringContainsString('flags set successfully', $result);
        $this->assertStringContainsString('Processing check', $result);
        $this->assertStringContainsString('First non-repeating letter: k', $result);
    }

    public function testNonRepeatingPunctuationFromValidFileFoundSuccessfully(): void
    {
        $application = new Application('echo', '1.0.0');
        $command = new LetterPlay();

        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--input' => 'public/valid-input.txt',
            '--format' => 'non-repeating',
            '--include-punctuation' => true,
        ]);
        $result = $commandTester->getDisplay(true);
        $this->assertStringContainsString('input option validated', $result);
        $this->assertStringContainsString('format option validated', $result);
        $this->assertStringContainsString('flags set successfully', $result);
        $this->assertStringContainsString('Processing check', $result);
        $this->assertStringContainsString('First non-repeating punctuation:', $result);
    }

    public function testLeastRepeatingSymbolFromValidFileFoundSuccessfully(): void
    {
        $application = new Application('echo', '1.0.0');
        $command = new LetterPlay();

        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--input' => 'public/valid-input.txt',
            '--format' => 'least-repeating',
            '--include-symbol' => true,
        ]);
        $result = $commandTester->getDisplay(true);
        $this->assertStringContainsString('input option validated', $result);
        $this->assertStringContainsString('format option validated', $result);
        $this->assertStringContainsString('flags set successfully', $result);
        $this->assertStringContainsString('Processing check', $result);
        $this->assertStringContainsString('First least-repeating symbol:', $result);
    }

    public function testMostRepeatingSymbolFromValidFileFoundSuccessfully(): void
    {
        $application = new Application('echo', '1.0.0');
        $command = new LetterPlay();

        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--input' => 'public/valid-input.txt',
            '--format' => 'most-repeating',
            '--include-symbol' => true,
            '--include-punctuation' => true,
            '--include-letter' => true,
        ]);
        $result = $commandTester->getDisplay(true);
        $this->assertStringContainsString('input option validated', $result);
        $this->assertStringContainsString('format option validated', $result);
        $this->assertStringContainsString('flags set successfully', $result);
        $this->assertStringContainsString('Processing check', $result);
        $this->assertStringContainsString('First most-repeating symbol:', $result);
        $this->assertStringContainsString('First most-repeating punctuation:', $result);
        $this->assertStringContainsString('First most-repeating letter:', $result);
    }

}