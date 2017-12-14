<?php

namespace CWP\Core\Search;

use SilverStripe\FullTextSearch\Solr\Stores\SolrConfigStore,
    SilverStripe\FullTextSearch\Solr\Solr;

/**
 * Class CwpSolrConfigStore
 *
 * Uploads configuration to Solr via the PHP proxy CWP uses to filter requests
 */
class CwpSolrConfigStore implements SolrConfigStore
{
    /**
     * @var string
     */
    protected $remote = '';

    /**
     * @var string
     */
    protected $url = '';

    /**
     * @param array $config
     */
    public function __construct($config)
    {
        $options = Solr::solr_options();

        $this->url = implode('', array(
            'http://',
            isset($config['auth']) ? $config['auth'] . '@' : '',
            $options['host'] . ':' . $options['port'],
            $config['path']
        ));
        $this->remote = $config['remotepath'];
    }

    /**
     *
     * @param string $index
     * @param string $file
     * @return void
     */
    public function uploadFile($index, $file)
    {
        $this->uploadString($index, basename($file), file_get_contents($file));
    }

    /**
     *
     * @param type $index
     * @param type $filename
     * @param type $string
     * @return void
     */
    public function uploadString($index, $filename, $string)
    {
        $targetDir = "{$this->url}/config/$index";

        file_get_contents($targetDir . '/' . $filename, false, stream_context_create(array('http' => array(
            'method' => 'POST',
            'header' => 'Content-type: application/octet-stream',
            'content' => (string) $string
        ))));
    }

    /**
     *
     * @param string $index
     * @return string
     */
    public function instanceDir($index)
    {
        return $this->remote ? "{$this->remote}/$index" : $index;
    }

}
