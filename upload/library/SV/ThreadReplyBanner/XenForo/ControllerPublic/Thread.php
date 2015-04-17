<?php

class SV_ThreadReplyBanner_XenForo_ControllerPublic_Thread extends XFCP_SV_ThreadReplyBanner_XenForo_ControllerPublic_Thread
{
    public function actionEdit()
    {
        $response = parent::actionEdit();
        if ($response instanceof XenForo_ControllerResponse_View && !empty($response->params['thread']))
        {
            $threadModel = $this->_getThreadModel();
            if ($threadModel->CanManageThreadReplyBanner($response->params['thread']))
            {
                $response->params['canEditThreadReplyBanner'] = true;
                $response->params['thread']['banner'] = $threadModel->GetRawThreadReplyBanner($response->params['thread']['thread_id']);
            }
        }
        return $response;
    }

    public function actionSave()
    {
        $threadId = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);

        $threadModel = $this->_getThreadModel();
        $thread = $threadModel->getThreadById($threadId);
        if ($threadModel->CanManageThreadReplyBanner($thread))
        {
            SV_ThreadReplyBanner_Globals::$controller = $this;
        }
        return parent::actionSave();
    }

    public function actionIndex()
    {
        $response = parent::actionIndex();

        if ($response instanceof XenForo_ControllerResponse_View && !empty($response->params['thread']))
        {
            $response->params['thread']['banner'] = $this->_getThreadModel()->GetThreadReplyBanner($response->params['thread']);
        }
        return $response;
    }

    public function actionAddReply()
    {
        $response = parent::actionReply();

        if ($response instanceof XenForo_ControllerResponse_View && !empty($response->params['thread']))
        {
            $response->params['thread']['banner'] = $this->_getThreadModel()->GetThreadReplyBanner($response->params['thread']);
        }
        return $response;
    }
}