<?php

namespace ToolboxBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\EnumNodeDefinition;
use Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Form\Exception\InvalidConfigurationException;
use ToolboxBundle\Calculator\Bootstrap4\ColumnCalculator;
use ToolboxBundle\Calculator\Bootstrap4\SlideColumnCalculator;
use ToolboxBundle\Resolver\ContextResolver;
use ToolboxBundle\ToolboxConfig;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('toolbox');
        $rootNode = $treeBuilder->getRootNode();

        $this->getConfigNode($rootNode);
        $this->addContextNode($rootNode);

        $rootNode
            ->children()
                ->scalarNode('context_resolver')->defaultValue(ContextResolver::class)->end()
            ->end();

        return $treeBuilder;
    }

    public function addContextNode(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('context')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->append($this->buildContextSettingsNode())
                            ->append($this->buildFlagsConfiguration())
                            ->append($this->buildAreasSection(true))
                            ->append($this->buildAreasSection())
                            ->append($this->buildCkEditorConfigSection())
                            ->append($this->buildImageThumbnailSection())
                            ->append($this->buildAreasAppearanceConfiguration('areas_appearance'))
                            ->append($this->buildAreasAppearanceConfiguration('snippet_areas_appearance'))
                            ->append($this->buildAreaBlockConfiguration())
                            ->append($this->buildThemeConfiguration())
                            ->append($this->buildDataAttributeConfiguration())
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    public function getConfigNode(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->append($this->buildFlagsConfiguration())
                ->append($this->buildAreasSection(true))
                ->append($this->buildAreasSection())
                ->append($this->buildCkEditorConfigSection())
                ->append($this->buildImageThumbnailSection())
                ->append($this->buildAreasAppearanceConfiguration('areas_appearance'))
                ->append($this->buildAreasAppearanceConfiguration('snippet_areas_appearance'))
                ->append($this->buildAreaBlockConfiguration())
                ->append($this->buildThemeConfiguration())
                ->append($this->buildDataAttributeConfiguration())
            ->end();
    }

    protected function buildContextSettingsNode(): ArrayNodeDefinition
    {
        $treeBuilder = new ArrayNodeDefinition('settings');

        $treeBuilder
            ->beforeNormalization()
                ->ifTrue(function ($v) {
                    return $v['merge_with_root'] === false && (!empty($v['disabled_areas']));
                })
                ->then(function ($v) {
                    @trigger_error('Toolbox context conflict: "merge_with_root" is disabled but there are defined elements in "disabled_areas"', E_USER_ERROR);
                })
            ->end()
            ->beforeNormalization()
                ->ifTrue(function ($v) {
                    return $v['merge_with_root'] === false && (!empty($v['enabled_areas']));
                })
                ->then(function ($v) {
                    @trigger_error('Toolbox context conflict: "merge_with_root" is disabled but there are defined elements in "enabled_areas"', E_USER_ERROR);
                })
            ->end()
            ->isRequired()
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('merge_with_root')->defaultValue(true)->end()
                ->variableNode('disabled_areas')->defaultValue([])->end()
                ->variableNode('enabled_areas')->defaultValue([])->end()
            ->end();

        return $treeBuilder;
    }

    protected function buildFlagsConfiguration(): ArrayNodeDefinition
    {
        $treeBuilder = new ArrayNodeDefinition('flags');

        $treeBuilder
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('strict_column_counter')->defaultValue(false)->end()
                ->booleanNode('use_dynamic_links')->defaultValue(false)->end()
            ->end();

        return $treeBuilder;
    }

    protected function buildCkEditorConfigSection(): ArrayNodeDefinition
    {
        $treeBuilder = new ArrayNodeDefinition('ckeditor');

        $treeBuilder
            ->addDefaultsIfNotSet()
            ->children()
                ->variableNode('config')->defaultValue([])->end()
                ->variableNode('global_style_sets')->defaultValue([])->end()
                ->arrayNode('area_editor')
                    ->children()
                        ->variableNode('config')
                            ->validate()->ifEmpty()->thenUnset()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('object_editor')
                    ->children()
                        ->variableNode('config')
                            ->validate()->ifEmpty()->thenUnset()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    protected function buildImageThumbnailSection(): ArrayNodeDefinition
    {
        $treeBuilder = new ArrayNodeDefinition('image_thumbnails');

        $treeBuilder
            ->useAttributeAsKey('name')
            ->prototype('scalar')->end();

        return $treeBuilder;
    }

    protected function buildAreasAppearanceConfiguration(string $type): ArrayNodeDefinition
    {
        $treeBuilder = new ArrayNodeDefinition($type);

        $treeBuilder
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('disallowed')
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('allowed')
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    protected function buildAreaBlockConfiguration(): ArrayNodeDefinition
    {
        $treeBuilder = new ArrayNodeDefinition('area_block_configuration');

        $treeBuilder
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('toolbar')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('width')->defaultValue(172)->end()
                        ->integerNode('buttonWidth')->defaultValue(168)->end()
                        ->integerNode('buttonMaxCharacters')->defaultValue(20)->end()
                    ->end()
                ->end()
                ->variableNode('groups')->defaultNull()->end()
                ->enumNode('controlsAlign')->values(['top', 'right', 'left'])->defaultValue('top')->end()
                ->enumNode('controlsTrigger')->values(['hover', 'fixed'])->defaultValue('hover')->end()
            ->end();

        return $treeBuilder;
    }

    protected function buildThemeConfiguration(): ArrayNodeDefinition
    {
        $treeBuilder = new ArrayNodeDefinition('theme');

        $treeBuilder
            ->children()
                ->scalarNode('layout')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('default_layout')
                    ->defaultValue(false)
                ->end()
                ->arrayNode('calculators')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('column_calculator')->defaultValue(ColumnCalculator::class)->end()
                        ->scalarNode('slide_calculator')->defaultValue(SlideColumnCalculator::class)->end()
                    ->end()
                ->end()
                ->arrayNode('grid')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('grid_size')->min(0)->defaultValue(12)->end()
                        ->arrayNode('column_store')
                            ->useAttributeAsKey('name')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('breakpoints')
                            ->performNoDeepMerging()
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('identifier')->isRequired()->end()
                                    ->scalarNode('name')->defaultValue(null)->end()
                                    ->scalarNode('description')->defaultValue(null)->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('wrapper')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                    ->performNoDeepMerging()
                    ->beforeNormalization()
                        ->ifTrue(function ($v) {
                            return is_array($v) && !isset($v['wrapper_classes']);
                        })
                        ->then(function ($v) {
                            return array('wrapper_classes' => $v);
                        })
                    ->end()
                        ->children()
                            ->arrayNode('wrapper_classes')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('tag')->end()
                                        ->scalarNode('class')->end()
                                        ->scalarNode('attr')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    protected function buildDataAttributeConfiguration(): ArrayNodeDefinition
    {
        $treeBuilder = new ArrayNodeDefinition('data_attributes');

        $treeBuilder
            ->useAttributeAsKey('name')
            ->prototype('array')
            ->beforeNormalization()
                ->ifTrue(function ($v) {
                    return is_array($v) && !isset($v['values']);
                })
                ->then(function ($v) {
                    return array('values' => $v);
                })
            ->end()
            ->children()
                ->variableNode('values')->end()
            ->end()
        ->end();

        return $treeBuilder;
    }

    protected function buildAreasSection(bool $internalTypes = false): ArrayNodeDefinition
    {
        $treeBuilder = new ArrayNodeDefinition($internalTypes ? 'areas' : 'custom_areas');

        $treeBuilder
            ->validate()
                ->ifTrue(function ($v) use ($internalTypes) {
                    if ($internalTypes === false) {
                        return false;
                    }

                    return count(array_diff(array_keys($v), ToolboxConfig::TOOLBOX_TYPES)) > 0;
                })
                ->then(function ($v) {
                    $invalidTags = array_diff(array_keys($v), ToolboxConfig::TOOLBOX_TYPES);

                    throw new InvalidConfigurationException(sprintf(
                        'Invalid elements in toolbox "area" configuration: %s. to add custom areas, use the "custom_area" node. allowed tags for "area" are: %s',
                        implode(', ', $invalidTags),
                        implode(', ', ToolboxConfig::TOOLBOX_TYPES)
                    ));
                })
            ->end()
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->validate()
                    ->ifTrue(function ($v) {
                        $tabs = $v['tabs'];

                        return count($tabs) > 0 && count(array_filter($v['config_elements'], function($configElement) use ($tabs) {
                            return !array_key_exists($configElement['tab'], $tabs);
                        })) > 0;
                    })
                    ->then(function ($v) {
                        @trigger_error(
                            sprintf('Missing or wrong area tab definition in config_elements. Available tabs are: %s', implode(', ', array_keys($v['tabs']))),
                            E_USER_ERROR
                        );
                    })
                ->end()
                ->validate()
                    ->ifTrue(function ($v) {
                        $tabs = $v['tabs'];

                        return count($tabs) === 0 && count(array_filter($v['config_elements'], function($configElement) {
                            return $configElement['tab'] !== null;
                        })) > 0;
                    })
                    ->then(function ($v) {
                        @trigger_error('Unknown configured area tabs in config_elements. No tabs have been defined', E_USER_ERROR);
                    })
                ->end()
                ->children()
                    ->append($this->buildConfigElementsTabSection())
                    ->append($this->buildConfigElementsSection($internalTypes))
                    ->variableNode('config_parameter')->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    protected function buildConfigElementsTabSection(): ArrayNodeDefinition
    {
        $treeBuilder = new ArrayNodeDefinition('tabs');

        $treeBuilder
            ->useAttributeAsKey('name')
            ->prototype('scalar')
            ->validate()
                ->ifNull()->thenEmptyArray()
            ->end()
            ->end();

        return $treeBuilder;
    }

    protected function buildConfigElementsSection(bool $internalTypes = false): ArrayNodeDefinition
    {
        $treeBuilder = new ArrayNodeDefinition('config_elements');

        if ($internalTypes === true) {
            //@todo: get them dynamically!!
            $allowedTypes = array_merge(ToolboxConfig::CORE_TYPES, ToolboxConfig::CUSTOM_TYPES);

            $typeNode = new EnumNodeDefinition('type');
            $typeNode->isRequired()->values($allowedTypes)->end();
        } else {
            $typeNode = new ScalarNodeDefinition('type');
            $typeNode->isRequired()->end();
        }

        $treeBuilder
            ->useAttributeAsKey('name')
            ->prototype('array')
            ->addDefaultsIfNotSet()
                ->children()
                    ->append($typeNode)
                    ->scalarNode('title')->defaultValue(null)->end()
                    ->scalarNode('description')->defaultValue(null)->end()
                    ->scalarNode('tab')->defaultValue(null)->end()
                    ->variableNode('config')->defaultValue([])->end()
                ->end()
                ->validate()
                    ->ifTrue(function ($v) {
                        return $v['enabled'] === false;
                    })
                    ->thenUnset()
                ->end()
                ->canBeUnset()
                ->canBeDisabled()
                ->treatnullLike(['enabled' => false])
            ->end();

        return $treeBuilder;
    }
}
