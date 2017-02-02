<?php namespace Shift\Console\Commands;

use League\Csv\Writer;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Yaml\Yaml;

final class ExportCommand extends \Knp\Command\Command
{
  /** @var PropertyAccessor */
  private $accessor;

  protected function configure()
  {
    $this
      ->setName('csv:export')
      ->setDescription('Sends an email notification when there are domains pending approval')
      ->addArgument('path', InputArgument::REQUIRED, 'The directory where the locale files are located')
      ->addArgument('output_path', InputArgument::OPTIONAL, 'The output path');

    parent::configure();

    $this->accessor = PropertyAccess::createPropertyAccessor();
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $path = rtrim($input->getArgument('path'), '/');
    $outputPath = $input->getArgument('output_path');

    if ($outputPath === null) {
      $outputPath = $path . '/locales.csv';
    }

    $csvWrite = Writer::createFromPath($outputPath, 'c');

    $glob = glob($path . '/*.yml');
    $data = [
      'header' => array_merge(['key'], array_fill(0, count($glob), ''))
    ];

    foreach ($glob as $index => $localeFile) {
      $locale = basename($localeFile, '.yml');
      $data['header'][$index+1] = $locale;
      $yaml = Yaml::parse(file_get_contents($localeFile));

      $flattenedYaml = [];
      $this->flatten($flattenedYaml, $yaml, '');

      foreach ($flattenedYaml as $key => $value) {
        if ( ! array_key_exists($key, $data)) {
          $data[$key] = array_merge([$key], array_fill(0, count($glob), ''));
        }

        $data[$key][$index+1] = $value;
      }
    }

    $csvWrite->insertAll($data);
  }

  private function flatten(&$newData, $currentNode, $currentKey = '')
  {
    if ( ! is_array($currentNode)) {
      $newData[$currentKey] = $currentNode;
      return;
    }

    foreach ($currentNode as $key => $node) {
      $this->flatten($newData, $node, $currentKey ? "{$currentKey}.{$key}" : $key);
    }
  }
}