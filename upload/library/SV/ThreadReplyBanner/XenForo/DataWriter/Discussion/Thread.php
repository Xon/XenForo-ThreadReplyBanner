<?php

class SV_ThreadReplyBanner_XenForo_DataWriter_Discussion_Thread extends XFCP_SV_ThreadReplyBanner_XenForo_DataWriter_Discussion_Thread
{
    const banner_length = 65536;

    protected function _discussionPreSave()
    {
        $ret = parent::_discussionPreSave();
        if (!empty(SV_ThreadReplyBanner_Globals::$controller))
        {
            $threadModel = $this->_getThreadModel();
            $threadId = $this->get('thread_id');
            $old_banner = $threadModel->GetRawThreadReplyBanner($threadId);
            $new_banner = SV_ThreadReplyBanner_Globals::$controller->getInput()->filterSingle('thread_reply_banner', XenForo_Input::STRING);
            if($new_banner != $old_banner)
            {
                if (strlen($new_banner) <= self::banner_length)
                {
                    $threadModel->UpdateThreadReplyBanner($threadId, $new_banner);

                    $thread = $this->getMergedData();
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
}