.. include:: ../Includes.txt

.. _configuration:

=============
Configuration
=============

The extension can either be configured in the content element plugin settings or via TypoScript.

Typical Example
===============

Minimal example of TypoScript:

.. code-block:: typoscript

   plugin.tx_gkhrssimport_pi1 {
      templateFile = EXT:website_package/Resources/Private/Templates/RssImport.html
   }

.. _configuration-typoscript:

TypoScript Reference
====================

.. container:: table-row

   Property
      rssFeed
   Data type
      string
   Default
      (none)
   Description
      The URL to the RSS feed

      **Example:** ::

         plugin.tx_gkhrssimport_pi1.rssFeed = https://example.com/feed/

.. container:: table-row

   Property
      itemsLimit
   Data type
      int
   Default
      10
   Description
      Maximum items to show

      **Example:** ::

         plugin.tx_gkhrssimport_pi1.itemsLimit = 10

.. container:: table-row

   Property
      itemLength
   Data type
      int
   Default
      500
   Description
      Length of item content

      **Example:** ::

         plugin.tx_gkhrssimport_pi1.itemLength = 500

.. container:: table-row

   Property
      headerLength
   Data type
      int
   Default
      80
   Description
      Length of header description

      **Example:** ::

         plugin.tx_gkhrssimport_pi1.headerLength = 80

.. container:: table-row

   Property
      target
   Data type
      string
   Default
      (none)
   Description
      Target of URL

      **Example:** ::

         plugin.tx_gkhrssimport_pi1.target = _blank

.. container:: table-row

   Property
      logoWidth
   Data type
      int
   Default
      140
   Description
      Width of logo image

      **Example:** ::

         plugin.tx_gkhrssimport_pi1.logoWidth = 140

.. container:: table-row

   Property
      errorMessage
   Data type
      string
   Default
      It's not possible to reach the RSS file...
   Description
      Error message

      **Example:** ::

         plugin.tx_gkhrssimport_pi1.errorMessage = It's not possible to reach the RSS file...

.. container:: table-row

   Property
      dateFormat
   Data type
      int/string
   Default
      3
   Description
      Date format

      **Possible values:**

      +--------+----------------+----------------------+
      | Format | Representation | Preview              |
      +========+================+======================+
      | 1      | %A, %d. %B %Y  | Monday, 12. May 2008 |
      +--------+----------------+----------------------+
      | 2      | %d. %B %Y      | \12. May 2008        |
      +--------+----------------+----------------------+
      | 3      | %e/%m - %Y     | 12/5-2008            |
      +--------+----------------+----------------------+

      Additionally you can define your own format which is supported by `strftime <https://www.php.net/strftime>`__.

      **Examples:** ::

         plugin.tx_gkhrssimport_pi1.dateFormat = 3
         plugin.tx_gkhrssimport_pi1.dateFormat = %m-%d-%Y %H:%M:%S

.. container:: table-row

   Property
      stripHTML
   Data type
      int
   Default
      2
   Description
      Remove HTML from RSS feed

      **Example:** ::

         plugin.tx_gkhrssimport_pi1.stripHTML = 2

.. container:: table-row

   Property
      flexCache
   Data type
      int
   Default
      (none)
   Description
      RSS Feed cache time in seconds

      **Example:** ::

         plugin.tx_gkhrssimport_pi1.flexCache = 86400

.. container:: table-row

   Property
      templateFile
   Data type
      string
   Default
      EXT:gkh_rss_import/Resources/Private/Templates/RssImport.html
   Description
      Template File (the path needs to start with "EXT:" if you set it via TypoScript, otherwise set the template via
      Plugin settings in the backend)

      **Example:** ::

         plugin.tx_gkhrssimport_pi1.templateFile = EXT:gkh_rss_import/Resources/Private/Templates/RssImport.html

