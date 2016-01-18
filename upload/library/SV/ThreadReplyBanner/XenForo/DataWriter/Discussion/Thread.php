<?php

class SV_ThreadReplyBanner_XenForo_DataWriter_Discussion_Thread extends XFCP_SV_ThreadReplyBanner_XenForo_DataWriter_Discussion_Thread
{
    const banner_length = 65536;

    protected $new_banner = '';

    protected function _discussionPreSave()
    {
        $ret = parent::_discussionPreSave();
        if (!empty(SV_ThreadReplyBanner_Globals::$controller))
        {
            $threadModel = $this->_getThreadModel();
            $thread = $this->getMergedData();
            $forum = $this->_getForumData();

            if (empty($forum) || !$threadModel->canManageThreadReplyBanner($thread, $forum))
            {
                $this->error(new XenForo_Phrase('sv_no_permissions_edit_thread_reply_banner'));
            }

            if ($this->isUpdate())
            {
                $old_banner = $threadModel->getRawThreadReplyBanner($this->get('thread_id'));
            }
            else
            {
                $old_banner = '';
            }

            $new_banner = SV_ThreadReplyBanner_Globals::$controller->getInput()->filterSingle('thread_reply_banner', XenForo_Input::STRING);
            if($new_banner != $old_banner)
            {
                if (strlen($new_banner) <= self::banner_length)
                {
                    $this->new_banner = $new_banner;

                    if (empty($new_banner))
                    {
                        XenForo_Model_Log::logModeratorAction('thread', $thread, 'replybanner_deleted');
                    }
                    else
                    {
                        XenForo_Model_Log::logModeratorAction('thread', $thread, 'replybanner', array('banner' => $new_banner));
                    }
                }
                else
                {
                    $this->error(new XenForo_Phrase('please_enter_value_using_x_characters_or_fewer', array('count' => self::banner_length)));
                }
            }
        }
        return $ret;
    }

    protected function _discussionPostSave()
    {
        parent::_discussionPostSave();
        $this->_getThreadModel()->updateThreadReplyBanner($this->get('thread_id'), $this->new_banner);
    }

    protected function _discussionPostDelete()
    {
        parent::discussionPostDelete();
        $this->_db->query(
            'delete from xf_thread_banner where thread_id = ?'
        , $this->get('thread_id'));
    }
}