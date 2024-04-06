<?php

function table( array $data ): string {
	$columns = [];
	foreach ( $data as $row_key => $row ) {
		foreach ( $row as $cell_key => $cell ) {
			$length = strlen( $cell );
			if ( empty( $columns[ $cell_key ] ) || $columns[ $cell_key ] < $length ) {
				$columns[ $cell_key ] = $length;
			}
		}
	}

	$table = '';
	foreach ( $data as $row_key => $row ) {
		foreach ( $row as $cell_key => $cell ) {
			$table .= str_pad( $cell, $columns[ $cell_key ] ) . '  ';
		}
		$table .= PHP_EOL;
	}

	return $table;

}
