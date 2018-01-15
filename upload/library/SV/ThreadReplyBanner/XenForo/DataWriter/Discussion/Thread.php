<?php

class SV_ThreadReplyBanner_XenForo_DataWriter_Discussion_Thread extends XFCP_SV_ThreadReplyBanner_XenForo_DataWriter_Discussion_Thread
{

    /** @var SV_ThreadReplyBanner_DataWriter_ThreadBanner */
    protected $bannerDw = null;

    protected function _discussionPreSave()
    {
        parent::_discussionPreSave();
        if (empty(SV_ThreadReplyBanner_Globals::$banner) && !$this->isUpdate())
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

        $banner = $threadModel->getRawThreadReplyBanner($this->get('thread_id'));

        $this->bannerDw = XenForo_DataWriter::create('SV_ThreadReplyBanner_DataWriter_ThreadBanner', self::ERROR_SILENT);
        $this->bannerDw->setOption(SV_ThreadReplyBanner_DataWriter_ThreadBanner::OPTION_THREAD, $this->getMergedData());
        if ($banner)
        {
            $this->bannerDw->setExistingData($banner, true);
        }
        else
        {
            $this->bannerDw->set('thread_id', $this->get('thread_id'));
        }
        $this->bannerDw->bulkSet(SV_ThreadReplyBanner_Globals::$banner);
        $this->bannerDw->preSave();
        $this->_errors += $this->bannerDw->getErrors();
    }

    protected function _postSaveAfterTransaction()
    {
        parent::_postSaveAfterTransaction();
        if ($this->bannerDw && $this->bannerDw->hasChanges())
        {
            $this->bannerDw->save();
        }
    }

    protected function _discussionPostDelete()
    {
        parent::_discussionPostDelete();

        /** @var SV_ThreadReplyBanner_DataWriter_ThreadBanner $dw */
        $dw = XenForo_DataWriter::create('SV_ThreadReplyBanner_DataWriter_ThreadBanner', self::ERROR_SILENT);
        $dw->setExistingData($this->get('thread_id'));
        $this->bannerDw->setOption(SV_ThreadReplyBanner_DataWriter_ThreadBanner::OPTION_THREAD, $this->getMergedData());
        $dw->delete();
    }
}
