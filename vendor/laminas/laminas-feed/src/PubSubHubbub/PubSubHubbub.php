<?php

declare(strict_types=1);

namespace Laminas\Feed\PubSubHubbub;

use Laminas\Escaper\Escaper;
use Laminas\Feed\Reader;
use Laminas\Http;

use function is_string;
use function str_replace;

class PubSubHubbub
{
    /**
     * Verification Modes
     */
    public const VERIFICATION_MODE_SYNC  = 'sync';
    public const VERIFICATION_MODE_ASYNC = 'async';

    /**
     * Subscription States
     */
    public const SUBSCRIPTION_VERIFIED    = 'verified';
    public const SUBSCRIPTION_NOTVERIFIED = 'not_verified';
    public const SUBSCRIPTION_TODELETE    = 'to_delete';

    /** @var Escaper */
    protected static $escaper;

    /**
     * Singleton instance if required of the HTTP client
     *
     * @var null|Http\Client
     */
    protected static $httpClient;

    /**
     * Simple utility function which imports any feed URL and
     * determines the existence of Hub Server endpoints. This works
     * best if directly given an instance of Laminas\Feed\Reader\Atom|Laminas\Feed\Reader\Rss
     * to leverage off.
     *
     * @param  string|Reader\Feed\AbstractFeed $source
     * @return array<array-key, mixed>|null
     * @throws Exception\InvalidArgumentException
     */
    public static function detectHubs($source)
    {
        if (is_string($source)) {
            $feed = Reader\Reader::import($source);
        } elseif ($source instanceof Reader\Feed\AbstractFeed) {
            $feed = $source;
        } else {
            throw new Exception\InvalidArgumentException(
                'The source parameter was'
                . ' invalid, i.e. not a URL string or an instance of type'
                . ' Laminas\Feed\Reader\Feed\AbstractFeed'
            );
        }
        return $feed->getHubs();
    }

    /**
     * Allows the external environment to make laminas-oauth use a specific
     * Client instance.
     *
     * @return void
     */
    public static function setHttpClient(Http\Client $httpClient)
    {
        static::$httpClient = $httpClient;
    }

    /**
     * Return the singleton instance of the HTTP Client. Note that
     * the instance is reset and cleared of previous parameters GET/POST.
     * Headers are NOT reset but handled by this component if applicable.
     *
     * @return Http\Client
     */
    public static function getHttpClient()
    {
        if (! isset(static::$httpClient)) {
            static::$httpClient = new Http\Client();
        } else {
            static::$httpClient->resetParameters();
        }
        return static::$httpClient;
    }

    /**
     * Simple mechanism to delete the entire singleton HTTP Client instance
     * which forces a new instantiation for subsequent requests.
     *
     * @return void
     */
    public static function clearHttpClient()
    {
        static::$httpClient = null;
    }

    /**
     * Set the Escaper instance
     *
     * If null, resets the instance
     *
     * @return void
     */
    public static function setEscaper(?Escaper $escaper = null)
    {
        static::$escaper = $escaper;
    }

    /**
     * Get the Escaper instance
     *
     * If none registered, lazy-loads an instance.
     *
     * @return Escaper
     */
    public static function getEscaper()
    {
        if (null === static::$escaper) {
            static::setEscaper(new Escaper());
        }
        return static::$escaper;
    }

    /**
     * RFC 3986 safe url encoding method
     *
     * @param  string $string
     * @return string
     */
    public static function urlencode($string)
    {
        $escaper    = static::getEscaper();
        $rawencoded = $escaper->escapeUrl($string);
        return str_replace('%7E', '~', $rawencoded);
    }
}
