<?php

class SV_ThreadReplyBanner_EditHistoryHandler_ThreadBanner extends XenForo_EditHistoryHandler_Abstract
{
    protected $_prefix = 'threads';

    protected function _getContent($contentId, array $viewingUser)
    {
        $threadModel = $this->_getThreadModel();

        $thread = $threadModel->getThreadById(
            $contentId,
            [
                'join'                    => XenForo_Model_Thread::FETCH_FORUM |
                                             XenForo_Model_Thread::FETCH_FORUM_OPTIONS |
                                             XenForo_Model_Thread::FETCH_USER,
                'permissionCombinationId' => $viewingUser['permission_combination_id']
            ]
        );
        if ($thread)
        {
            $thread['permissions'] = XenForo_Permission::unserializePermissions($thread['node_permission_cache']);
            $thread['reply_banner'] = $threadModel->getRawThreadReplyBanner($contentId);
        }

        return $thread;
    }

    protected function _canViewHistoryAndContent(array $content, array $viewingUser)
    {
        $threadModel = $this->_getThreadModel();

        return $threadModel->canViewThreadAndContainer($content, $content, $null, $content['permissions'], $viewingUser) &&
               $threadModel->canManageThreadReplyBanner($content, $content, $null, $content['permissions'], $viewingUser);
    }

    protected function _canRevertContent(array $content, array $viewingUser)
    {
        $threadModel = $this->_getThreadModel();

        return $threadModel->canManageThreadReplyBanner($content, $content, $null, $content['permissions'], $viewingUser);
    }

    public function getText(array $content)
    {
        //$bbCodeParser = $this->getThreadReplyBannerParser();
        //$bannerText = $this->renderThreadReplyBanner($bbCodeParser, $content['reply_banner']);
        return htmlspecialchars($content['reply_banner']['raw_text']);
    }

    public function getTitle(array $content)
    {
        //return new XenForo_Phrase('post_in_thread_x', array('title' => $content['title']));
        return htmlspecialchars($content['title']); // TODO
    }

    public function getBreadcrumbs(array $content)
    {
        /* @var $nodeModel XenForo_Model_Node */
        $nodeModel = XenForo_Model::create('XenForo_Model_Node');

        $node = $nodeModel->getNodeById($content['node_id']);
        if ($node)
        {
            $crumb = $nodeModel->getNodeBreadCrumbs($node);
            $crumb[] = [
                'href'  => XenForo_Link::buildPublicLink('full:threads', $content),
                'value' => $content['title']
            ];

            return $crumb;
        }
        else
        {
            return [];
        }
    }

    public function getNavigationTab()
    {
        return 'forums';
    }

    public function formatHistory($string, XenForo_View $view)
    {
        return htmlspecialchars($string);
    }

    public function revertToVersion(array $content, $revertCount, array $history, array $previous = null)
    {
        $banner = $content['reply_banner'];
        /** @var SV_ThreadReplyBanner_DataWriter_ThreadBanner $dw */
        $dw = XenForo_DataWriter::create('SV_ThreadReplyBanner_DataWriter_ThreadBanner', XenForo_DataWriter::ERROR_SILENT);
        $dw->setExistingData($banner);
        $dw->set('raw_text', $history['old_text']);
        $dw->set('banner_edit_count', $dw->get('thread_title_edit_count') + 1);
        if ($dw->get('banner_edit_count'))
        {
            $dw->set('banner_last_edit_date', $previous['edit_date']);
            $dw->set('banner_last_edit_user_id', $previous['edit_user_id']);
        }

        return $dw->save();
    }

    protected $_threadModel = null;

    /**
     * @return SV_ThreadReplyBanner_XenForo_Model_Thread|XenForo_Model_Thread|XenForo_Model
     * @throws XenForo_Exception
     */
    protected function _getThreadModel()
    {
        if ($this->_threadModel === null)
        {
            $this->_threadModel = XenForo_Model::create('XenForo_Model_Thread');
        }

        return $this->_threadModel;
    }
}
