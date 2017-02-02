<?php namespace Shift\Console\Commands;

use Knp\Command\Command;
use League\Csv\Reader;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Yaml\Yaml;

final class ImportCommand extends Command
{
  /** @var PropertyAccessor */
  private $accessor;

  protected function configure()
  {
    $this
      ->setName('csv:import')
      ->setDescription('Generate .yml translation files from a source .csv file')
      ->addArgument('path', InputArgument::REQUIRED, 'Path to the .csv file from which to import')
      ->addArgument('output', InputArgument::OPTIONAL, 'The output directory path where the individual .yml files will be writtem');

    parent::configure();

    $this->accessor = PropertyAccess::createPropertyAccessor();
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $path = $input->getArgument('path');
    $outputPath = trim($input->getArgument('output'));

    if ($outputPath === '') {
      $outputPath = dirname($path);
    }

    $csvReader = Reader::createFromPath($path);

    $data = [];

    $lines = $csvReader->fetchAssoc();
    foreach ($lines as $line) {
      $key = $line['key'];
      $propertyPath = '[' . implode('][', explode('.', $key)) . ']';

      foreach ($line as $locale => $value) {
        if ($locale === 'key') {
          continue;
        }

        if ( ! array_key_exists($locale, $data)) {
          $data[$locale] = [];
        }

        if ($value !== '') {
          $this->accessor->setValue($data[$locale], $propertyPath, $value);
        }

      }
    }

    foreach ($data as $locale => $translations) {
      $yamlData = [];
      $this->normalize($yamlData, $translations);

      file_put_contents("{$outputPath}/{$locale}.yml", Yaml::dump($yamlData, 9999));
    }
  }

  private function normalize(&$newData, $currentNode, $currentKey = '')
  {
    if ( ! is_array($currentNode)) {
      $propertyPath = '[' . implode('][', explode('.', $currentKey)) . ']';
      $this->accessor->setValue($newData, $propertyPath, $currentNode);

      return;
    }

    foreach ($currentNode as $key => $node) {
      $this->normalize($newData, $node, $currentKey ? "{$currentKey}.{$key}" : $key);
    }
  }
}