<?php

class SV_ThreadReplyBanner_XenForo_DataWriter_Discussion_Thread extends XFCP_SV_ThreadReplyBanner_XenForo_DataWriter_Discussion_Thread
{
    const banner_length = 65536;

    protected $new_banner = null;

    protected function _discussionPreSave()
    {
        parent::_discussionPreSave();
        if (empty(SV_ThreadReplyBanner_Globals::$controller))
        {
            return;
        }
        /** @var SV_ThreadReplyBanner_XenForo_Model_Thread $threadModel */
        $threadModel = $this->_getThreadModel();
        $thread = $this->getMergedData();
        $forum = $this->_getForumData();

        if (empty($forum) || !$threadModel->canManageThreadReplyBanner($thread, $forum))
        {
            return;
        }

        $old_banner = '';
        if ($this->isUpdate())
        {
            $banner = $threadModel->getRawThreadReplyBanner($this->get('thread_id'));
            if (!empty($banner['raw_text']))
            {
                $old_banner = $banner['raw_text'];
            }
        }

        $new_banner = SV_ThreadReplyBanner_Globals::$controller->getInput()->filterSingle('thread_reply_banner', XenForo_Input::STRING);
        if ($new_banner == $old_banner)
        {
            return;
        }
        if (strlen($new_banner) <= self::banner_length)
        {
            $this->new_banner = $new_banner;

            if (empty($new_banner))
            {
                XenForo_Model_Log::logModeratorAction('thread', $thread, 'replybanner_deleted');
            }
            else
            {
                XenForo_Model_Log::logModeratorAction('thread', $thread, 'replybanner', ['banner' => $new_banner]);
            }
        }
        else
        {
            $this->error(new XenForo_Phrase('please_enter_value_using_x_characters_or_fewer', ['count' => self::banner_length]));
        }
    }

    protected function _postSaveAfterTransaction()
    {
        parent::_postSaveAfterTransaction();
        if ($this->new_banner !== null)
        {
            /** @var SV_ThreadReplyBanner_XenForo_Model_Thread $threadModel */
            $threadModel = $this->_getThreadModel();
            $threadModel->updateThreadReplyBanner($this->get('thread_id'), $this->new_banner);
        }
    }

    protected function _discussionPostDelete()
    {
        parent::_discussionPostDelete();
        $this->_db->query(
            'DELETE FROM xf_thread_banner WHERE thread_id = ?'
            , $this->get('thread_id')
        );
    }
}
