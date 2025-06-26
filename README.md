# ELM vocabulary field

Provides a field type for ELM controlled vocabularies.

## Installation

Include the repository in your project's `composer.json` file:

    "repositories": [
        ...
        {
            "type": "vcs",
            "url": "https://github.com/EuropeanUniversityFoundation/elm_vocabulary_field"
        }
    ],

Then you can require the package as usual:

    composer require euf/elm_vocabulary_field

Finally, install the module:

    drush en elm_vocabulary_field

## Usage

The **ELM controlled vocabulary** field type becomes available in the Field UI under the _Selection list_ category, so it can be added to any fieldable entity like any other field type.

### Field storage settings

Each field storage requires **selecting a controlled vocabulary**. Once the field has data, this cannot be changed.

### Field instance settings

Each field instance contains configuration to **allow selection** of only part of the controlled vocabulary. If left blank, all values from the vocabulary are allowed.

### Field widget settings

The default field widget is a selection list. Select options may be prefixed by their respective key.

### Field formatter settings

The default field formatter also allows items to be prefixed by their respective key.

## Data source

ELM controlled vocabularies are retrieved from this [PHP library](https://packagist.org/packages/euf/elm_vocabularies) which is required in `composer.json`.

## ROADMAP

  - Twig support
