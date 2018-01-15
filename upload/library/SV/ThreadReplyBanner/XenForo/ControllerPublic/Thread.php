<?php

class SV_ThreadReplyBanner_XenForo_ControllerPublic_Thread extends XFCP_SV_ThreadReplyBanner_XenForo_ControllerPublic_Thread
{
    public function actionReplyBannerHistory()
    {
        $this->_request->setParam('content_type', 'thread_banner');
        $this->_request->setParam('content_id', $this->_input->filterSingle('thread_id', XenForo_Input::UINT));

        return $this->responseReroute(
            'XenForo_ControllerPublic_EditHistory',
            'index'
        );
    }

    public function actionEdit()
    {
        $response = parent::actionEdit();
        if ($response instanceof XenForo_ControllerResponse_View &&
            !empty($response->params['thread']) &&
            !empty($response->params['forum']))
        {
            /** @var SV_ThreadReplyBanner_XenForo_Model_Thread $threadModel */
            $threadModel = $this->_getThreadModel();
            if ($threadModel->canManageThreadReplyBanner($response->params['thread'], $response->params['forum']))
            {
                $response->params['canEditThreadReplyBanner'] = true;
                $response->params['thread']['rawbanner'] = $threadModel->getRawThreadReplyBanner($response->params['thread']['thread_id']);
            }
        }

        return $response;
    }

    public function actionSave()
    {
        SV_ThreadReplyBanner_Globals::$controller = $this;

        return parent::actionSave();
    }

    public function actionIndex()
    {
        $response = parent::actionIndex();

        if ($response instanceof XenForo_ControllerResponse_View && !empty($response->params['thread']))
        {
            /** @var SV_ThreadReplyBanner_XenForo_Model_Thread $threadModel */
            $threadModel = $this->_getThreadModel();

            $response->params['thread']['banner'] = $threadModel->getThreadReplyBanner($response->params['thread']);
        }

        return $response;
    }

    public function actionAddReply()
    {
        $response = parent::actionAddReply();
        if ($response instanceof XenForo_ControllerResponse_View && !empty($response->params['thread']))
        {
            /** @var SV_ThreadReplyBanner_XenForo_Model_Thread $threadModel */
            $threadModel = $this->_getThreadModel();

            $response->params['thread']['banner'] = $threadModel->getThreadReplyBanner($response->params['thread']);
        }

        return $response;
    }

    public function actionReply()
    {
        $response = parent::actionReply();
        if ($response instanceof XenForo_ControllerResponse_View && !empty($response->params['thread']))
        {
            /** @var SV_ThreadReplyBanner_XenForo_Model_Thread $threadModel */
            $threadModel = $this->_getThreadModel();

            $response->params['thread']['banner'] = $threadModel->getThreadReplyBanner($response->params['thread']);
        }

        return $response;
    }
}
