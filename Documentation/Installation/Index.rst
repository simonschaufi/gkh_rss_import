.. include:: ../Includes.txt

.. _installation:

Installation
============

.. important::

   Use the versions 6.x for TYPO3 8, 7.x for TYPO3 9 and 10 and 9.x for TYPO3 11.

The extension needs to be installed as any other extension of TYPO3 CMS:

#. **Use composer**: Use `composer require gkh/gkh-rss-import`.

#. **Get it from the Extension Manager:** Press the "Retrieve/Update" button and search for the extension key *gkh_rss_import* and import the extension from the repository.

#. **Get it from typo3.org:** You can always get current version from `https://extensions.typo3.org/extension/gkh_rss_import/ <https://extensions.typo3.org/extension/gkh_rss_import/>`_ by downloading the zip version. Upload the file afterwards in the Extension Manager.

Preparation: Include static TypoScript
--------------------------------------

The extension ships some TypoScript code which needs to be included.

#. Go to the root page of your site.

#. Go to the **Template module** and select *Info/Modify*.

#. Press the link **Edit the whole template record** and go to the tab *Includes*.

#. Select **RSS Feed Import (gkh_rss_import)** at the field *Include static (from extensions):*
