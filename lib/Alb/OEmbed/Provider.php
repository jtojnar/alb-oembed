<?php

namespace Alb\OEmbed;

use Kdyby\Curl;

/**
 * An oEmbed Provider
 */
class Provider
{
    /**
     * The endpoint answers in json format
     */
    const TYPE_JSON = 'json';

    /**
     * The endpoint answers in xml format
     */
    const TYPE_XML = 'xml';

    protected $endpoint;
    protected $type;

    /**
     * @param string $endpoint Endpoint URL
     * @param mixed $type Endpoint type (either TYPE_JSON or TYPE_XML)
     */
    public function __construct($endpoint, $type)
    {
        $this->endpoint = $endpoint;
        $this->type = $type;
    }

    public function getEndpoint()
    {
        return $this->endpoint;
    }

    public function getType()
    {
        return $this->type;
    }

    /**
     * Requests the provider
     *
     * @param string $resourceUrl Resource URL
     * @param array $params Request params (e.g. maxheight, maxwidth)
     */
    public function request($resourceUrl, array $params = array())
    {
        $params = array('url' => $resourceUrl) + $params;

        $url = $this->setUrlParams($this->endpoint, $params);

        $data = $this->fetchUrl($url);

        if (self::TYPE_JSON === $this->type) {
            return $this->createResponseFromJson($data);
        } else if (self::TYPE_XML === $this->type) {
            return $this->createResponseFromXml($data);
        }
    }

    /**
     * Creates a response from a JSON string
     *
     * @param string $json A JSON string
     * @return Response A Response instance or NULL
     */
    public function createResponseFromJson($json)
    {
        if (null === $data = json_decode($json)) {
            return;
        }
        return new Response($data);
    }

    /**
     * Creates a response from a XML string
     *
     * @param string $xml A XML string
     * @return Response A Response instance or NULL
     */ 
    public function createResponseFromXml($xml)
    {
        $dom = new \DomDocument($xml);
        if (!$dom->loadXML($xml)) {
            return;
        }

        $data = array();

        foreach($dom->childNodes as $root) {
            if ($root->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }
            foreach($root->childNodes as $node) {
                $name = $node->nodeName;
                $text = $node->textContent;
                if (preg_match('/^<!\[CDATA\[(.*)]]>$/', $text)) {
                    $text = substr($text, 9, -3);
                }
                $data[$name] = $text;
            }
        }

        return new Response($data);
    }

    protected function fetchUrl($url)
    {
        $curl = new Curl\Request($url);
        $curl->setUserAgent('Mozilla/5.0 (alb-oembed)');
        $response = $curl->get();
        return $response->getResponse();
    }

    protected function setUrlParams($url, $params)
    {
        $str = http_build_query($params);

        if (false !== strpos($url, '?')) {
            $url .= '&';
        } else {
            $url .= '?';
        }

        return $url . $str;
    }
}

