SIB - Symfony Installation Bundle
=================================

This is a bundle for symfony that handle symfony project instalation. The goal of this project is to fit this deploy workflow.

    1. Zip distributable project
    2. Extract to another server
    3. Do configuration after install without touching its configuration file (via browser/console command)
    4. If there is a patch in future, that patch can be uploaded to currently installed project and do repeat step #3
    5. Get happy client!

Feature
-------

    - Enable/disable maintenance mode
    - Instalation process via browser or console command
    - Hook into 'after' installation process via listener
    - Translation (currently support Indonesia and English)

Doc
---

Please refer to the [Documentation](doc/index.md) and DocBlock provided in the source code.
