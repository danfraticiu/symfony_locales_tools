<?php namespace Shift\Console\Commands;

use Knp\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Yaml\Yaml;

final class NormalizeCommand extends Command
{
  /** @var PropertyAccessor */
  private $accessor;

  protected function configure()
  {
    $this
      ->setName('yaml:normalize')
      ->setDescription('Normalizes a yaml file')
      ->addArgument('path', InputArgument::REQUIRED, 'File to be normalized');

    parent::configure();

    $this->accessor = PropertyAccess::createPropertyAccessor();
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $filePath = $input->getArgument('path');

    if ( ! file_exists($filePath)) {
      $output->writeln('<error>File not found</error>');
    }

    $content = Yaml::parse(file_get_contents($filePath));
    $newData = [];

    $this->normalize($newData, $content);

    file_put_contents($filePath . '.updated', Yaml::dump($newData, 99));
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