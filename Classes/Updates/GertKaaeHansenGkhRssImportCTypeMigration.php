<?php

declare(strict_types=1);

namespace GertKaaeHansen\GkhRssImport\Updates;

use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\AbstractListTypeToCTypeUpdate;

#[UpgradeWizard('gertkaaehansenGkhRssImportCTypeMigration')]
final class GertKaaeHansenGkhRssImportCTypeMigration extends AbstractListTypeToCTypeUpdate
{
    public function getTitle(): string
    {
        return 'Migrate "GertKaaeHansen GkhRssImport" plugins to content elements.';
    }

    public function getDescription(): string
    {
        return 'The "GertKaaeHansen GkhRssImport" plugins are now registered as content element. Update migrates existing records and backend user permissions.';
    }

    /**
     * This must return an array containing the "list_type" to "CType" mapping
     *
     *  Example:
     *
     *  [
     *      'pi_plugin1' => 'pi_plugin1',
     *      'pi_plugin2' => 'new_content_element',
     *  ]
     *
     * @return array<string, string>
     */
    protected function getListTypeToCTypeMapping(): array
    {
        return [
            'gkh_rss_import_pi1' => 'gkh_rss_import_pi1',
        ];
    }
}
