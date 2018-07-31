<?php
namespace AppBundle\Controller;


use AppBundle\Entity\Theme;
use AppBundle\Model\ConstModel;
use FOS\UserBundle\Model\UserInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ThemeController extends Controller
{

    /**
     * @Route("/theme/change-user-theme", name="change_user_theme")
     * @Method("POST")
     */
    public function changeUserTheme(Request $request){

        $primaryColor= $request->get('primary-color-input');
        $textColor= $request->get('text-color-input');
        $secondaryColor= $request->get('secondary-color-input');
        $navbarColor= $request->get('navbar-color-input');
        $bodyColor= $request->get('body-color-input');
        $mainLogo= $request->get('main-logo-input');
        $notification= $request->get('notification-color-input');
        $inputTextColor= $request->get('inputs-color-input');
        $inNightMode = $request->get('inNightMode');


        $user = $this->getUser();
        if($user == null || !$user instanceof UserInterface){
            return new JsonResponse(0);
        }

        $em = $this->getDoctrine()->getManager();

        if($inNightMode != null){
            $theme = $this->getDoctrine()->getRepository('AppBundle:Theme')->find(ConstModel::NIGHT_MODE_THEME_ID);
            $user->setTheme(($inNightMode == 1)?$theme:null);
            $user->setInNightMode($inNightMode);
            $em->persist($user);
            $em->flush();
            return new JsonResponse(1);
        }

        if($user->getUserCanCustomise() == false){
            return new JsonResponse(0);
        }


        if($user->getTheme() == null){
            $theme = new Theme();
            $user->setTheme($theme);
        } else {
            if($user->getTheme()->getId() == ConstModel::NIGHT_MODE_THEME_ID){
                $theme = new Theme();
                $user->setTheme($theme);
            } else {
                $theme = $user->getTheme();
            }
        }

        if ($mainLogo) {
            $path = 'img/themes/user-avatars/';
            $fileName = md5(uniqid()) . time() . '.jpg';
            $rawImage = file_get_contents($mainLogo);
            file_put_contents($path. $fileName, $rawImage);
            $mainLogo = '/'.$path . $fileName;
        } else {
            $mainLogo = $user->getSite()->getMainLogo();
        }

        $theme->setPrimaryColor(($primaryColor)?$primaryColor:ConstModel::PRIMARY_COLOR);
        $theme->setPrimaryTextColor(($textColor)?$textColor:ConstModel::TEXT_COLOR);
        $theme->setSecondaryColor(($secondaryColor)?$secondaryColor:ConstModel::SECONDARY_COLOR);
        $theme->setNavBackgroundColor(($navbarColor)?$navbarColor:ConstModel::NAVBAR_COLOR);
        $theme->setBodyBackgroundColor(($bodyColor)?$bodyColor:ConstModel::BODY_COLOR);
        $theme->setNotificationBodyColor(($notification)?$notification:ConstModel::NOTIFICATION_COLOR); //todo ste
        $user->getSite()->setMainLogo(($mainLogo)?$mainLogo:ConstModel::MAIN_LOGO);
        $theme->setBodyInputsTextColor(($inputTextColor)?$inputTextColor:ConstModel::BODY_INPUT_TEXT_COLOR);
        $user->setInNightMode(0);
        $em->persist($theme);
        $em->flush();

        return new JsonResponse(1);
    }


    public function processLessAction()
    {
        $user = $this->getUser();
        if($user == null || !$user instanceof UserInterface){
            $theme = $this->getDefaultTheme();
            $mainLogo = ConstModel::MAIN_LOGO;
        }else{
            if($user->getTheme()){
                $theme = $user->getTheme();
            } else {
                $theme = $this->getDefaultTheme();
            }
            $mainLogo = ($user->getSite())?$user->getSite()->getMainLogo():ConstModel::MAIN_LOGO;
        }


        $template = ':Less:theme.less.twig';
        $template = $this->renderView($template, array(
            'theme' => $theme,
            'mainLogo' => $mainLogo
        ));
        $lessc = new \lessc();
        $lessc->setFormatter('compressed');
        $compiled = $lessc->compile($template);
        return new Response($compiled);
    }

    public function customiseThemeFormAction(){

        $user = $this->getUser();
        $translator = $this->get('translator');

        if($user == null || !$user instanceof UserInterface){
            return new Response('<p>'.$translator->trans('You Have No access To This Section').'</p>');
        }

        if($user->getUserCanCustomise() != true){
            return new Response('<p>'.$translator->trans('You Have No access To This Section').'</p>');
        }


        if($user->getTheme()){
            $theme = $user->getTheme();
        } else {
            $theme = $this->getDefaultTheme();
        }

       return $this->render(':parts:customise-theme.html.twig',array(
            'theme' => $theme
        ));
    }


    public function getDefaultTheme(){
        $theme = new Theme();
        $theme->setPrimaryColor(ConstModel::PRIMARY_COLOR);
        $theme->setPrimaryTextColor(ConstModel::TEXT_COLOR);
        $theme->setSecondaryColor(ConstModel::SECONDARY_COLOR);
        $theme->setNavBackgroundColor(ConstModel::NAVBAR_COLOR);
        $theme->setBodyBackgroundColor(ConstModel::BODY_COLOR);
        $theme->setNotificationBodyColor(ConstModel::NOTIFICATION_COLOR);
        $theme->setBodyInputsTextColor(ConstModel::BODY_INPUT_TEXT_COLOR);
        return $theme;
    }

}