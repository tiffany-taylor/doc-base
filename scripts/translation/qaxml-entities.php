<?php /*
+----------------------------------------------------------------------+
| Copyright (c) 1997-2025 The PHP Group                                |
+----------------------------------------------------------------------+
| This source file is subject to version 3.01 of the PHP license,      |
| that is bundled with this package in the file LICENSE, and is        |
| available through the world-wide-web at the following url:           |
| https://www.php.net/license/3_01.txt.                                |
| If you did not receive a copy of the PHP license and are unable to   |
| obtain it through the world-wide-web, please send a note to          |
| license@php.net, so we can mail you a copy immediately.              |
+----------------------------------------------------------------------+
| Authors:     André L F S Bacci <ae php.net>                          |
+----------------------------------------------------------------------+

# Description

Compare XML entities usage between two XML leaf/fragment files.       */

require_once __DIR__ . '/libqa/all.php';

$argv   = new ArgvParser( $argv );
$ignore = new OutputIgnore( $argv ); // may exit.
$urgent = $argv->consume( "--urgent" ) != null;

$ents = [];
foreach( $argv->residual() as $ent )
{
    if ( strlen( $ent ) > 2 && $ent[0] == '-' && $ent[1] != '-' )
    {
        $ents[] = '&' . substr( $ent , 1) . ';';
        $argv->use( $ent );
    }
}
$argv->complete();

$list = SyncFileList::load();

foreach ( $list as $file )
{
    $source = $file->sourceDir . '/' . $file->file;
    $target = $file->targetDir . '/' . $file->file;
    $output = new OutputBuffer( "# qaxml.e" , $target , $ignore );

    [ $_ , $s , $_ ] = XmlFrag::loadXmlFragmentFile( $source );
    [ $_ , $t , $_ ] = XmlFrag::loadXmlFragmentFile( $target );

    adornEntities( $s );
    adornEntities( $t );

    if ( implode( "\n" , $s ) == implode( "\n" , $t ) )
        continue;

    $sideCount = [];

    foreach( $s as $v )
        $sideCount[$v] = [ 0 , 0 ];
    foreach( $t as $v )
        $sideCount[$v] = [ 0 , 0 ];

    foreach( $s as $v )
        $sideCount[$v][0] += 1;
    foreach( $t as $v )
        $sideCount[$v][1] += 1;

    foreach( $sideCount as $ent => $_ )
        if ( in_array( $ent , $ents ) )
            $sideCount[ $ent ] = [ 0 , 0 ];

    foreach( $sideCount as $k => $v )
        if ( $v[0] != $v[1] )
            $output->addDiff( $k , $v[0] , $v[1] );

    if ( $urgent )
    {
        $count = 0;
        if ( $output->contains( "&chapters" ) )
            $count++;
        if ( $output->contains( "&features" ) )
            $count++;
        if ( $output->contains( "&language" ) )
            $count++;
        if ( $output->contains( "&reference" ) )
            $count++;
        if ( $output->contains( "&security" ) )
            $count++;
        if ( $count == 0 )
            continue;
    }

    $output->print();
}

function adornEntities( array & $list )
{
    foreach( $list as & $item )
        $item = '&' . $item . ';';
}