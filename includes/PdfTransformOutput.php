<?php

class PdfTransformOutput extends ThumbnailImage {
    function __construct( $file, $url, $path = false, $parameters = [] ) {
        $this->file = $file;
        $this->url = $url;
        $this->path = $path;
        $this->width = round( $parameters['width'] );
        $this->height = round( $parameters['height'] );
    }

    function toHtml( $options = [] ) {
        if ( count( func_get_args() ) == 2 ) {
            throw new MWException( __METHOD__ . ' called in the old style' );
        }

        $attribs = [
            'src' => $this->url,
            'width' => $this->width,
            'height' => $this->height
        ];

        if ( !empty( $options['custom-url-link'] ) ) {
            $linkAttribs = [ 'href' => $options['custom-url-link'] ];
            if ( !empty( $options['title'] ) ) {
                $linkAttribs['title'] = $options['title'];
            }
            if ( !empty( $options['custom-target-link'] ) ) {
                $linkAttribs['target'] = $options['custom-target-link'];
            } elseif ( !empty( $options['parser-extlink-target'] ) ) {
                $linkAttribs['target'] = $options['parser-extlink-target'];
            }
            if ( !empty( $options['parser-extlink-rel'] ) ) {
                $linkAttribs['rel'] = $options['parser-extlink-rel'];
            }
        } elseif ( !empty( $options['custom-title-link'] ) ) {
            $title = $options['custom-title-link'];
            $linkAttribs = [
                'href' => $title->getLinkURL(),
                'title' => empty( $options['title'] ) ? $title->getFullText() : $options['title']
            ];
        } elseif ( !empty( $options['desc-link'] ) ) {
            $linkAttribs = $this->getDescLinkAttribs(
                empty( $options['title'] ) ? null : $options['title'],
                $query
            );
        } elseif ( !empty( $options['file-link'] ) ) {
            $linkAttribs = [ 'href' => $this->file->getUrl() ];
        } else {
            $linkAttribs = false;
            if ( !empty( $options['title'] ) ) {
                $attribs['title'] = $options['title'];
            }
        }

        // if ( empty( $options['no-dimensions'] ) ) {
        //     $attribs['width'] = $this->width;
        //     $attribs['height'] = $this->height;
        // }
        // if ( !empty( $options['valign'] ) ) {
        //     $attribs['style'] = "vertical-align: {$options['valign']}";
        // }
        // if ( !empty( $options['img-class'] ) ) {
        //     $attribs['class'] = $options['img-class'];
        // }
        // if ( isset( $options['override-height'] ) ) {
        //     $attribs['height'] = $options['override-height'];
        // }
        // if ( isset( $options['override-width'] ) ) {
        //     $attribs['width'] = $options['override-width'];
        // }

        // // Additional densities for responsive images, if specified.
        // // If any of these urls is the same as src url, it'll be excluded.
        // $responsiveUrls = array_diff( $this->responsiveUrls, [ $this->url ] );
        // if ( !empty( $responsiveUrls ) ) {
        //     $attribs['srcset'] = Html::srcSet( $responsiveUrls );
        // }

        Hooks::run( 'ThumbnailBeforeProduceHTML', [ $this, &$attribs, &$linkAttribs ] );

        $result = $this->linkWrap( $linkAttribs, Xml::element( 'embed', $attribs ) );
        // wfDebug("[PdfHandler] rendered html " . print_r($result));
        return $result;
    }
}
