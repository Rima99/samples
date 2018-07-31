<?php
namespace AppBundle\Controller;

use AppBundle\Entity\SiteSettings;
use AppBundle\Entity\Team;
use AppBundle\Entity\TeamMembers;
use AppBundle\Model\ConstModel;
use Doctrine\ORM\EntityManager;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManager;
use FOS\UserBundle\Model\UserManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

/**
 * @Route("/{_locale}/admin/" , requirements={
 *      "_locale": "am|en|ru"
 *  },
 *  defaults={
 *      "_locale": "en"
 *  }
 * ))
 */
class AdminRoleController extends Controller
{

    /************************************ TEAM PAGES AND PARTS OF ADMIN ************************************/

    /**
     * Add operator or admin MODAL
     * @param Request $request
     * @param string $_locale
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addOperatorsAction(Request $request, $_locale = "en"){

        if($this->checkAccess()){ throw new AccessDeniedException; }

        $roleRepository = $this->getDoctrine()->getRepository('AppBundle:UserRoleInChat');
        $teamRepository = $this->getDoctrine()->getRepository('AppBundle:Team');
        $roles = $roleRepository->findAll();
        $teams = $teamRepository->findBy([],["teamName"=>"ASC"]);

        return $this->render('pages/admin/parts/admin-add-user-modal.html.twig',['roles'=>$roles,'teams'=>$teams]);

    }


    public function addNewTeamAction(){
        if($this->checkAccess()){ throw new AccessDeniedException; }

        $userRepository = $this->getDoctrine()->getRepository('AppBundle:User');
        $users = $userRepository->findBy([],['userFirstName'=>'ASC']);

        return $this->render(':pages/admin/parts:admin-add-new-team.html.twig',array(
            'users' => $users
        ));
    }


    /**
     * Get all statistics of site for admin dashboard
     * @param Request $request
     * @param string $_locale
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAdminDashboardStatisticsAction(Request $request, $_locale = "en"){

        if($this->checkAccess()){ throw new AccessDeniedException; }

        $roleRepository = $this->getDoctrine()->getRepository('AppBundle:UserRoleInChat');
        $roles = $roleRepository->findAll();

        return $this->render('pages/admin/parts/admin-dashboard-statistics.twig',['roles'=>$roles]);
    }

    /**
     * Get latest rates for admin dashboard
     * @param Request $request
     * @param string $_locale
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAdminDashboardLatestRatesAction(Request $request, $_locale = "en"){

        if($this->checkAccess()){ throw new AccessDeniedException; }

        $roleRepository = $this->getDoctrine()->getRepository('AppBundle:UserRoleInChat');
        $roles = $roleRepository->findAll();

        return $this->render('pages/admin/parts/admin-latest-rates.twig',['roles'=>$roles]);
    }

    /************************************ TEAM PAGES AND PARTS OF ADMIN ************************************/



    /************************************ TEAM PAGES OF ADMIN ************************************/

    /**
     * @Route("teams-and-members", name="admin_team_members"))
     */
    public function adminTeamMembersAction(){

        return $this->render('pages/admin/admin-team-members.html.twig');
    }


    /**
     * @param Request $request
     * @param string $_locale
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAdminTeamsListAction(Request $request, $_locale = "en"){

        if($this->checkAccess()){ throw new AccessDeniedException; }

        $roleRepository = $this->getDoctrine()->getRepository('AppBundle:UserRoleInChat');
        $roles = $roleRepository->findAll();

        return $this->render('pages/admin/parts/admin-teams.twig',['roles'=>$roles]);
    }


    /**
     * @param Request $request
     * @param string $_locale
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAdminTeamMembersListAction(Request $request, $_locale = "en"){

        if($this->checkAccess()){ throw new AccessDeniedException; }

        $roleRepository = $this->getDoctrine()->getRepository('AppBundle:UserRoleInChat');
        $teamRepository = $this->getDoctrine()->getRepository('AppBundle:Team');
        $roles = $roleRepository->findAll();
        $teams = $teamRepository->findBy([],["teamName"=>"ASC"]);

        return $this->render('pages/admin/parts/admin-team-members.twig',['roles'=>$roles,'teams'=>$teams]);
    }

    /************************************ TEAM PAGES OF ADMIN ************************************/





    /************************************ SETTINGS PAGE OF ADMIN ************************************/

    /**
     * @Route("site-settings", name="admin_site_settings")
     */
    public function adminSiteSettings(Request $request){

        if($this->checkAccess()){ throw new AccessDeniedException; }

        $em = $this->getDoctrine()->getManager();
        $translator = $this->get('translator');
        $siteId = $this->getUser()->getSite()->getId();
        $siteSettingsRepository = $this->getDoctrine()->getRepository('AppBundle:SiteSettings');
        $setting =  $siteSettingsRepository->findOneBy(['site'=>$siteId]);

        $formForSettings = "";
        if(null == $setting) {
            $this->get('session')->set('settingsExist',false);
            $setting = new SiteSettings();
        }else {
            $this->get('session')->set('settingsExist',true);
        }
        $formForSettings = $this->createFormBuilder($setting)
                ->add('wCanPutEmoji',CheckboxType::class,array('required' => false))
                ->add('wCanPutDocument',CheckboxType::class,array('required' => false))
                ->add('wCanCall',CheckboxType::class,array('required' => false))
                ->add('wCanAuthUser',CheckboxType::class,array('required' => false))
                ->add('wCanRate',CheckboxType::class,array('required' => false))
                ->add('save',SubmitType::class, array('label' => $translator->trans('SAVE')))
            ->getForm();
        
        $formForSettings->handleRequest($request);

        if ($formForSettings->isSubmitted() && $formForSettings->isValid()) {
            $setting = $formForSettings->getData();
            if($this->get('session')->get('settingsExist')){
                $em->merge($setting);$em->flush();
            }else{
                $setting->setSite( $this->getUser()->getSite() );
                $em->persist($setting);$em->flush();
            }
        }

        $roleRepository = $this->getDoctrine()->getRepository('AppBundle:UserRoleInChat');
        $roles = $roleRepository->findAll();

        return $this->render('pages/admin/admin-site-settings.html.twig',[
            'roles'             =>  $roles,
            'formForSettings'   =>  $formForSettings->createView()
        ]);
    }

    /************************************ SETTINGS PAGE OF ADMIN ************************************/






    /**
     * @Route("ajax/change-user-status", name="ajax_add_new_user")
     * @Method("POST")
     */
    public function addOperatorAjax(Request $request, $_locale = "en",UserManagerInterface $userManager,\Swift_Mailer $mailer){

        $translator = $this->get('translator');
        $userEmail = $request->get('user-email');
        $userRole = $request->get('user-role');
        $userTeam = $request->get('user-team');
        $userFirstName = $request->get('user-first-name');
        $userLastName = $request->get('user-last-name');
        $userPassword = $request->get('user-password');
        $userCanCustomise = $request->get('user-can-customize');


        if(!$userEmail || !$userRole || !$userFirstName || !$userLastName || !$userPassword || !$userTeam)
            return new JsonResponse(['type'=>'danger','message'=>$translator->trans('Some Required Parameters Are Missing !')]);

        $roleRepository = $this->getDoctrine()->getRepository('AppBundle:UserRoleInChat');
        $userRepository = $this->getDoctrine()->getRepository('AppBundle:User');
        $teamRepository = $this->getDoctrine()->getRepository('AppBundle:Team');
        $userSelectedRole = $roleRepository->find($userRole);
        $selectedTeam = $teamRepository->find($userTeam);
        $em = $this->getDoctrine()->getManager();
        if($userSelectedRole == null)
            return new JsonResponse(['type'=>'danger','message'=>$translator->trans('Please Select Valid Role')]);

        if($selectedTeam == null)
            return new JsonResponse(['type'=>'danger','message'=>$translator->trans('Please Select or Create Team')]);

        $user =$userManager->createUser();
        while (1){
            $userName = $userFirstName.$userLastName.rand(1000,9999);
            $userCheck = $userRepository->findOneBy(['username'=>$userName]);
            if($userCheck == null) {
                break;
            }
        }

        if($userCanCustomise){
            $userCanCustomise = true;
        } else {
            $userCanCustomise = false;
        }
        try{
            $user->setUsername($userName);
            $user->setEmail($userEmail);
            $user->setPlainPassword($userPassword);
            $user->setUserFirstName($userFirstName);
            $user->setUserLastName($userLastName);
            $user->setUserRoleInChat($userSelectedRole);
            $user->setUserStatus(false);
            $user->setUserCanCustomise($userCanCustomise);
            $user->setSite($this->getUser()->getSite());
            $user->setEnabled(true);
            $userManager->updateUser($user);

            $teamMember = new TeamMembers();
            $teamMember->setTeam($selectedTeam);
            $teamMember->setTeamMembers($user);
            $em->persist($teamMember);
            $em->flush();

        } catch (\Exception $exception){
            return new JsonResponse(['type'=>'warning','message'=>$translator->trans('User With This Email Already Exists')]);
        }


        $message = (new \Swift_Message('Hello Email'))
            ->setFrom(ConstModel::SITE_MAIN_EMAIL)
            ->setTo($userEmail)
            ->setBody(
                $this->renderView(
                    'email/user-add.html.twig',
                    array('user'=>$user,'password'=>$userPassword)
                ),
                'text/html'
            )
        ;
        $mailer->send($message);

        return new JsonResponse(['type'=>'success','message'=>$translator->trans('User Created Successfully. Username and Password Sent by Email')]);
    }

    /**
     * @Route("ajax/add-team", name="ajax_add_team")
     * @Method("POST")
     */
    public function addTeamAjax(Request $request, $_locale = "en",UserManagerInterface $userManager){

        $translator = $this->get('translator');
        if($this->checkAccess()){  return new JsonResponse(['type'=>'danger','message'=>$translator->trans('You Have No Access !')]); }

        $teamName = $request->get('team-name');
        $teamMembers = $request->get('team-members');
        $em = $this->getDoctrine()->getManager();
        $userRepository = $this->getDoctrine()->getRepository('AppBundle:User');

        if(!$teamName)
            return new JsonResponse(['type'=>'danger','message'=>$translator->trans('Some Required Parameters Are Missing !')]);


          $team = new Team();
          $team->setTeamAdmin($this->getUser());
          $team->setTeamName($teamName);
          $em->persist($team);

         if($teamMembers){
             foreach ($teamMembers as $member){
                 $user = $userRepository->find($member);
                 if($user != null){
                     $teamMember = new TeamMembers();
                     $teamMember->setTeam($team);
                     $teamMember->setTeamMembers($user);
                     $em->persist($teamMember);
                 }
             }
         }
          $em->flush();

        return new JsonResponse(['type'=>'success','data'=>json_encode(array('teamName'=>$team->getTeamName(),'teamId'=>$team->getId())),'message'=>$translator->trans('Team Created Successfully.')]);
    }


    /**
     * Check Access To Pages
     * @return bool
     */
    public function checkAccess(){
        $user = $this->getUser();

        if($user == null || !$user instanceof UserInterface) return true;

        if($user->getUserRoleInChat()->getId() != ConstModel::ROLE_ADMIN) return true;

        return false;
    }
}