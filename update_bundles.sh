#!/usr/bin/env bash
bold=$(tput bold)
normal=$(tput sgr0)

composer="$(which composer)"
php -d memory_limit=-1 $composer update os2display/core-bundle os2display/admin-bundle os2display/default-template-bundle os2display/media-bundle itk-os2display/aarhus-data-bundle itk-os2display/aarhus-second-template-bundle aakb/os2display-aarhus-templates itk-os2display/template-extension-bundle itk-os2display/lokalcenter-template-bundle itk-os2display/vimeo-bundle itk-os2display/campaign-bundle
