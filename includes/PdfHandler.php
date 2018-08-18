<?php
/**
 * Copyright © 2007 Martin Seidel (Xarax) <jodeldi@gmx.de>
 *
 * Inspired by djvuhandler from Tim Starling
 * Modified and written by Xarax
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

class PdfHandler extends MediaHandler {

    /**
     * @param File $file
     * @return bool
     */
    public function canRender( $file ) {
        return ( $file->getWidth() && $file->getHeight() );
    }

	/**
	 * @return array
	 */
	public function getParamMap() {
        return ['img_width' => 'width'];
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @return bool
	 */
	function validateParam( $name, $value ) {
		if ( in_array( $name, [ 'width', 'height' ] ) ) {
			return ( $value > 0 );
		}
		return false;
	}

	/**
	 * @param array $params
	 * @return bool|string
	 */
	function makeParamString( $params ) {
		if ( !isset( $params['width'] ) ) {
			return false;
		}
		return "{$params['width']}px";
	}

    /**
     * @param string $str
     * @return array|bool
     */
    function parseParamString( $str ) {
        $m = [];
        if ( preg_match( '/^(\d+)px$/', $str, $m ) ) {
            return [ 'width' => $m[1]];
        }
        return false;
    }

    /**
     * @param File $pdf
     * @param array &$params
     * @return bool
     */
    function normaliseParams( $pdf, &$params) {
        return true;
    }

	/**
	 * @return bool
	 */
	function isEnabled() {
		// global $wgPdfProcessor, $wgPdfPostProcessor, $wgPdfInfo;

		// if ( !isset( $wgPdfProcessor ) || !isset( $wgPdfPostProcessor ) || !isset( $wgPdfInfo ) ) {
		// 	wfDebug( "PdfHandler is disabled, please set the following\n" );
		// 	wfDebug( "variables in LocalSettings.php:\n" );
		// 	wfDebug( "\$wgPdfProcessor, \$wgPdfPostProcessor, \$wgPdfInfo\n" );
		// 	return false;
		// }
		return true;
	}

	/**
	 * @param File $file
	 * @return bool
	 */
	function mustRender( $file ) {
		return true;
	}

	/**
	 * @param File $image
	 * @param string $path
	 * @return PdfImage
	 * @suppress PhanUndeclaredProperty
	 */
	function getPdfImage( $image, $path ) {
		if ( !$image ) {
			$pdfimg = new PdfImage( $path );
		} elseif ( !isset( $image->pdfImage ) ) {
			$pdfimg = $image->pdfImage = new PdfImage( $path );
		} else {
			$pdfimg = $image->pdfImage;
		}
		return $pdfimg;
	}


    /**
     * @param File $image
     * @param string $path
     * @return array|bool
     */

    function getImageSize( $image, $path ) {
        // https://stackoverflow.com/questions/1523479/what-does-the-variable-this-mean-in-php
        $imageSize = $this->getPdfImage( $image, $path )->getImageSize();
        return $imageSize;
    }


	/**
	 * @param File $image
	 * @param string $dstPath
	 * @param string $dstUrl
	 * @param array $params
	 * @param int $flags
	 * @return MediaTransformError|MediaTransformOutput|ThumbnailImage|TransformParameterError
	 */
	function doTransform( $image, $dstPath, $dstUrl, $params, $flags = 0 ) {
        wfDebug("[PdfHandler] do transform params " . print_r($params, true));
        $width = $params['width'] ?: 400;
        $height = $params['height'] ?: 500;
        $srcPath = $image->getLocalRefPath();

		if ( $flags & self::TRANSFORM_LATER ) {
			return new PdfTransformOutput( $image, $dstUrl, false, [
				'width' => $width,
				'height' => $height
			] );
		}

        wfDebug("[PdfHandler] local src ref path: " . print_r($srcPath, true));
        wfDebug("[PdfHandler] dst path: " . print_r($dstPath, true));
        $cmd = wfEscapeShellArg(
            "cp",
            $srcPath,
            $dstPath
        );
        $retval = '';
        $err = wfShellExecWithStderr($cmd, $retval);
        wfDebug("[PdfHandler] copy return code: " . print_r($err, true));

        $removed = $this->removeBadFile( $dstPath, (bool)$err );
        return new PdfTransformOutput( $image, $dstUrl, $dstPath, [
            'width' => $width,
            'height' => $height
        ] );
	}


	public static $messages = [
		'main' => 'pdf-file-page-warning',
		'header' => 'pdf-file-page-warning-header',
		'info' => 'pdf-file-page-warning-info',
		'footer' => 'pdf-file-page-warning-footer',
	];

	/**
	 * Register a module with the warning messages in it.
	 * @param ResourceLoader &$resourceLoader
	 */
	static function registerWarningModule( &$resourceLoader ) {
		$resourceLoader->register( 'pdfhandler.messages', [
			'messages' => array_values( self::$messages ),
		] );
	}
}
