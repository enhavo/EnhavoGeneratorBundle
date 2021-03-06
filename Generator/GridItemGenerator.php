<?php

namespace Enhavo\Bundle\GeneratorBundle\Generator;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class GridItemGenerator extends Generator
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var ConsoleOutputInterface
     */
    protected $output;

    public function __construct(KernelInterface $kernel, EngineInterface $twigEngine)
    {
        parent::__construct($twigEngine);
        $this->kernel = $kernel;
        $this->output = new ConsoleOutput();
    }

    public function generateGridItem($bundleName, $itemName)
    {
        $bundle = $this->kernel->getBundle($bundleName);

        $this->generateDoctrineOrmFile($bundle, $itemName);
        $this->generateEntityFile($bundle, $itemName);
        $this->generateFormTypeFile($bundle, $itemName);
        $this->generateFactoryFile($bundle, $itemName);
        $this->generateTemplateFile($bundle, $itemName);

        $this->output->writeln('');
        $this->output->writeln('<options=bold>Add this to your enhavo.yml config file under enhavo_grid -> items:</>');
        $this->output->writeln($this->generateEnhavoConfigCode($bundle, $itemName));
        $this->output->writeln('');
    }

    protected function generateDoctrineOrmFile(BundleInterface $bundle, $itemName)
    {
        $filePath = $bundle->getPath() . '/Resources/config/doctrine/' . $itemName . '.orm.yml';
        if (file_exists($filePath)) {
            throw new \RuntimeException('Entity "' . $itemName . '" already exists in bundle "' . $bundle->getName() . '".');
        }

        $bundleNameSnakeCase = $this->camelCaseToSnakeCase($this->getBundleNameWithoutPostfix($bundle));
        $itemNameSnakeCase = $this->camelCaseToSnakeCase($itemName);

        if (!$this->renderFile(
            '@EnhavoGenerator/Generator/GridItem/doctrine.orm.yml.twig',
            $filePath,
            array(
                'bundle_namespace' => $bundle->getNamespace(),
                'item_name' => $itemName,
                'table_name' => $bundleNameSnakeCase . '_' . $itemNameSnakeCase
            )))
        {
            throw new \RuntimeException('Error writing file "' . $filePath . '".');
        }
    }

    protected function generateEntityFile(BundleInterface $bundle, $itemName)
    {
        $filePath = $bundle->getPath() . '/Entity/' . $itemName . '.php';
        if (file_exists($filePath)) {
            throw new \RuntimeException('Entity "' . $itemName . '" already exists in bundle "' . $bundle->getName() . '".');
        }

        if (!$this->renderFile(
            '@EnhavoGenerator/Generator/GridItem/entity.php.twig',
            $filePath,
            array(
                'namespace' => $bundle->getNamespace() . '\\Entity',
                'item_name' => $itemName
            )))
        {
            throw new \RuntimeException('Error writing file "' . $filePath . '".');
        }
    }

    protected function generateFormTypeFile(BundleInterface $bundle, $itemName)
    {
        $filePath = $bundle->getPath() . '/Form/Type/' . $itemName . 'Type.php';
        if (file_exists($filePath)) {
            throw new \RuntimeException('FormType "' . $itemName . 'Type" already exists in bundle "' . $bundle->getName() . '".');
        }

        if (!$this->renderFile(
            '@EnhavoGenerator/Generator/GridItem/form-type.php.twig',
            $filePath,
            array(
                'namespace' => $bundle->getNamespace() . '\\Form\\Type',
                'item_name' => $itemName,
                'item_namespace' => $bundle->getNamespace() . '\\Entity\\' . $itemName,
                'form_type_name' => $this->getFormTypeName($bundle, $itemName)
            )))
        {
            throw new \RuntimeException('Error writing file "' . $filePath . '".');
        }
    }

    protected function generateFactoryFile(BundleInterface $bundle, $itemName)
    {
        $filePath = $bundle->getPath() . '/Factory/' . $itemName . 'Factory.php';
        if (file_exists($filePath)) {
            throw new \RuntimeException('Factory class "' . $itemName . 'Factory" already exists in bundle "' . $bundle->getName() . '".');
        }

        if (!$this->renderFile(
            '@EnhavoGenerator/Generator/GridItem/factory.php.twig',
            $filePath,
            array(
                'namespace' => $bundle->getNamespace() . '\\Factory',
                'item_name' => $itemName
            )))
        {
            throw new \RuntimeException('Error writing file "' . $filePath . '".');
        }
    }

    protected function generateTemplateFile(BundleInterface $bundle, $itemName)
    {
        $filePath = $bundle->getPath() . '/Resources/views/Theme/Grid/' . $this->camelCaseToSnakeCase($itemName, true) . '.html.twig';
        if (file_exists($filePath)) {
            throw new \RuntimeException('Frontend template file "' . $filePath . '" already exists.');
        }

        if (!$this->renderFile(
            '@EnhavoGenerator/Generator/GridItem/template.html.twig',
            $filePath,
            array(
                'item_name' => $itemName,
            )))
        {
            throw new \RuntimeException('Error writing file "' . $filePath . '".');
        }
    }

    protected function generateEnhavoConfigCode(BundleInterface $bundle, $itemName)
    {
        $itemNameSpace = $bundle->getNamespace() . '\\Entity\\' . $itemName;
        $formTypeNamespace = $bundle->getNamespace() . '\\Form\\Type\\' . $itemName . 'Type';
        $template = $bundle->getName() . ':Theme/Grid:' . $this->camelCaseToSnakeCase($itemName, true) . '.html.twig';
        $factoryNamespace = $bundle->getNamespace() . '\\Factory\\' . $itemName . 'Factory';

        return $this->twigEngine->render('@EnhavoGenerator/Generator/GridItem/enhavo_config_entry.yml.twig', array(
            'item_name' => $itemName,
            'bundle_name' => $bundle->getName(),
            'item_name_snake_case' => $this->camelCaseToSnakeCase($itemName),
            'item_namespace' => $itemNameSpace,
            'form_type_namespace' => $formTypeNamespace,
            'template' => $template,
            'factory_namespace' => $factoryNamespace
        ));
    }

    protected function getFormTypeName(BundleInterface $bundle, $itemName)
    {
        return
            $this->camelCaseToSnakeCase($this->getBundleNameWithoutPostfix($bundle))
            . '_' . $this->camelCaseToSnakeCase($itemName);
    }
}
