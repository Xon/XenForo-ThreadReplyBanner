<?php

class SV_ThreadReplyBanner_XenForo_Model_Thread extends XFCP_SV_ThreadReplyBanner_XenForo_Model_Thread
{
    /**
     * @param int $threadId
     * @return array
     */
    public function getRawThreadReplyBanner($threadId)
    {
        return $this->_getDb()->fetchRow("SELECT * FROM xf_thread_banner WHERE thread_id = ?", $threadId);
    }

    /**
     * @return XenForo_BbCode_Parser
     */
    public function getThreadReplyBannerParser()
    {
        return XenForo_BbCode_Parser::create(XenForo_BbCode_Formatter_Base::create('Base'));
    }

    /**
     * @param XenForo_BbCode_Parser $bbCodeParser
     * @param array                 $banner
     * @return string
     */
    public function renderThreadReplyBanner(XenForo_BbCode_Parser $bbCodeParser, array $banner)
    {
        return $bbCodeParser->render($banner['raw_text']);
    }

    /**
     * @param int $threadId
     * @return string
     */
    public function getThreadReplyBannerCacheId($threadId)
    {
        return 'thread_banner_' . $threadId;
    }

    /**
     * @param            $thread
     * @param array|null $nodePermissions
     * @param array|null $viewingUser
     * @return null|string
     */
    public function getThreadReplyBanner($thread, array $nodePermissions = null, array $viewingUser = null)
    {
        if (empty($thread['has_banner']))
        {
            return null;
        }

        $this->standardizeViewingUserReferenceForNode($thread['node_id'], $viewingUser, $nodePermissions);

        if (!XenForo_Permission::hasContentPermission($nodePermissions, 'sv_replybanner_show') &&
            !XenForo_Permission::hasContentPermission($nodePermissions, 'sv_replybanner_manage'))
        {
            return null;
        }

        if ($cacheObject = $this->_getCache(true))
        {
            $cacheId = $this->getThreadReplyBannerCacheId($thread['thread_id']);
            if ($bannerText = $cacheObject->load($cacheId, true))
            {
                return $bannerText;
            }
        }
        $bannerText = '';
        $banner = $this->getRawThreadReplyBanner($thread['thread_id']);

        if (!empty($banner['banner_state']))
        {
            $bbCodeParser = $this->getThreadReplyBannerParser();
            $bannerText = $this->renderThreadReplyBanner($bbCodeParser, $banner);

            if ($cacheObject)
            {
                $cacheObject->save($bannerText, $cacheId, [], 86400);
            }
        }

        return $bannerText;
    }

    /**
     * @param int   $threadId
     * @param array $banner
     */
    public function updateThreadBannerCache($threadId, array $banner = null)
    {
        $cache = $this->_getCache();
        if (!$cache)
        {
            return;
        }
        $cacheId = $this->getThreadReplyBannerCacheId($threadId);
        if ($banner === null || empty($banner['banner_state']))
        {
            $cache->remove($cacheId);
        }
        else
        {
            $bbCodeParser = $this->getThreadReplyBannerParser();
            $bannerText = $this->renderThreadReplyBanner($bbCodeParser, $banner);

            $cache->save($bannerText, $cacheId, [], 86400);
        }
    }

    /**
     * @param array      $thread
     * @param array      $forum
     * @param string     $errorPhraseKey
     * @param array|null $nodePermissions
     * @param array|null $viewingUser
     * @return bool
     * @throws XenForo_Exception
     */
    public function canManageThreadReplyBanner(array $thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
    {
        $this->standardizeViewingUserReferenceForNode($thread['node_id'], $viewingUser, $nodePermissions);

        return XenForo_Permission::hasContentPermission($nodePermissions, 'sv_replybanner_manage');
    }
}
