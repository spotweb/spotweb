<?php

interface Dao_CacheStore {
        # function calculateFilePath($cacheId, $cacheType, $metadata);

        function removeCacheItem($cacheId, $cachetype, $metaData);
        function getCacheContent($cacheId, $cacheType, $metaData);
        function putCacheContent($cacheId, $cacheType, $content, $metaData);
}
