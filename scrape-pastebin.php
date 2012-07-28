<?php
/**
 * Scrape a pastebin user's public pastes
 * save locally as slugified version of the paste title
 */

function scrape_pastebin_user( $user, $pages = 0 ) {

    // create the save folder based on username
    if ( ! is_dir( $user ) )
    {
        mkdir( $user ) or die( 'Could not create username directory: ' . $user );
    }

    /* get number of pastes */
    $html = new DOMDocument();
    $html->loadHTMLFile( 'http://pastebin.com/u/' . $user );
    $xml = simplexml_import_dom($html);

    $xpath = '//*[@id="content_left"]/div[1]/div[2]/div[2]/text()';
    $result = $xml->xpath($xpath);
    $pastes = preg_replace( '/Total Pastes: (\d+).*/', '$1', (string) current($result) );
    $total_pages = (int) ceil( $pastes / 100 );

    // if pages = 0, get all pages
    $pages = $pages < 1 ? $total_pages : $pages;
    // if pages > total_pages, get all pages
    $pages = $pages > $total_pages ? $total_pages : $pages;

    foreach ( range(1,$pages) as $page )
    {

        // we already have the first page results from above, re-use it
        if ( $page > 1 )
        {
            $html = new DOMDocument();

            $html->loadHTMLFile( 'http://pastebin.com/u/' . $user . '/' . $page );

            $xml = simplexml_import_dom( $html );
        }

        // get the title
        $xpath = '//table/tr/td[1]/a';
        $results = $xml->xpath($xpath);

        foreach ( $results as $result )
        {
            $slug = $user . '/' . preg_replace( '/[^\w]+/', '-', strtolower( (string) $result ) );
            if ( file_exists( $slug ) ) continue;

            $token = trim( current($result[0]['href']), '/' );
            $raw = file_get_contents( 'http://pastebin.com/raw.php?i=' . $token );

            echo basename( $slug ) . PHP_EOL;
            file_put_contents( $slug, $raw );
        }

    }
}

if ( $argc < 2 )
{
    die("Usage: php scrape-pastebin.php username" . PHP_EOL );
}

$username = trim( $argv[1] );

// example - get all pastes for username
scrape_pastebin_user( $username );


// example - get only the first page of pastes for username
// scrape_pastebin_user( 'username', 1 );


