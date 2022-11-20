<?php
namespace astuteo\pjeShared\twigextensions;

use astuteo\pjeShared\services\ItemLoader;
use astuteo\pjeShared\services\EntryLoader;
use astuteo\pjeShared\services\CategoryLoader;
use astuteo\pjeShared\services\MatrixLoader;
use astuteo\pjeShared\services\Spellout;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Extension extends AbstractExtension {

    public function getFilters()
    {
        return [
            new TwigFilter(
                'spellout',
                [Spellout::class,'number']
            ),
        ];
    }

    public function getFunctions(): array
    {
        return [

            new TwigFunction(
                'spellout',
                [Spellout::class,'number']
            ),

            new TwigFunction(
                'itemTemplates',
                [
                    ItemLoader::class,
                    'load'
                ],
                [
                    'is_safe' => [
                        'html',
                    ]
                ]
            ),
            new TwigFunction(
                'entryTemplates',
                [
                    EntryLoader::class,
                    'load'
                ],
                [
                    'is_safe' => [
                        'html',
                    ]
                ]
            ),
            new TwigFunction(
                'categoryTemplates',
                [
                    CategoryLoader::class,
                    'load'
                ],
                [
                    'is_safe' => [
                        'html',
                    ]
                ]
            ),
            new TwigFunction(
                'matrixTemplates',
                [
                    MatrixLoader::class,
                    'load'
                ],
                [
                    'is_safe' => [
                        'html',
                    ]
                ]
            )
        ];
    }
}
