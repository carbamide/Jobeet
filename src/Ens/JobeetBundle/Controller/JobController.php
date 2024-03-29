<?php

namespace Ens\JobeetBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Ens\JobeetBundle\Entity\Job;
use Ens\JobeetBundle\Form\JobType;

/**
* Job controller.
*
*/
class JobController extends Controller
{
    /**
    * Lists all Job entities.
    *
    */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        
        $categories = $em->getRepository('EnsJobeetBundle:Category')->getWithJobs();
        
        foreach($categories as $category)
        {
            $category->setActiveJobs($em->getRepository('EnsJobeetBundle:Job')->getActiveJobs($category->getID(), $this->container->getParameter('max_jobs_on_homepage')));
            $category->setMoreJobs($em->getRepository('EnsJobeetBundle:Job')->countActiveJobs($category->getId()) - $this->container->getParameter('max_jobs_on_homepage'));
        }
        
        return $this->render('EnsJobeetBundle:Job:index.html.twig', array(
        'categories' => $categories
        ));
    }

    /**
    * Finds and displays a Job entity.
    *
    */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('EnsJobeetBundle:Job')->getActiveJob($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Job entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('EnsJobeetBundle:Job:show.html.twig', array(
        'entity'      => $entity,
        'delete_form' => $deleteForm->createView(),        ));
    }

    public function previewAction($token)
    {
        $em = $this->getDoctrine()->getEntityManager();
 
        $entity = $em->getRepository('EnsJobeetBundle:Job')->findOneByToken($token);
 
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Job entity.');
        }
 
        $deleteForm = $this->createDeleteForm($entity->getId());
        $publishForm = $this->createPublishForm($entity->getToken());
        $extendForm = $this->createExtendForm($entity->getToken());
 
        return $this->render('EnsJobeetBundle:Job:show.html.twig', array(
        'entity'      => $entity,
        'delete_form' => $deleteForm->createView(),
        'publish_form' => $publishForm->createView(),
        'extend_form' => $extendForm->createView(),
        ));
    }
 
    public function publishAction($token)
    {
        $form = $this->createPublishForm($token);
        $request = $this->getRequest();
 
        $form->bindRequest($request);
 
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            $entity = $em->getRepository('EnsJobeetBundle:Job')->findOneByToken($token);
 
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Job entity.');
            }
 
            $entity->publish();
            $em->persist($entity);
            $em->flush();
 
            $this->get('session')->setFlash('notice', 'Your job is now online for 30 days.');
        }
 
        return $this->redirect($this->generateUrl('ens_job_preview', array(
        'company' => $entity->getCompanySlug(),
        'location' => $entity->getLocationSlug(),
        'token' => $entity->getToken(),
        'position' => $entity->getPositionSlug()
        )));
    }
 
    private function createPublishForm($token)
    {
        return $this->createFormBuilder(array('token' => $token))
        ->add('token', 'hidden')
        ->getForm()
        ;
    }
    
    /**
    * Displays a form to create a new Job entity.
    *
    */
    public function newAction()
    {
        $entity = new Job();
        $entity->setType('full-time');
        $form   = $this->createForm(new JobType(), $entity);
 
        return $this->render('EnsJobeetBundle:Job:new.html.twig', array(
        'entity' => $entity,
        'form'   => $form->createView()
        ));
    }

    /**
    * Creates a new Job entity.
    *
    */
    public function createAction()
    {
        $entity  = new Job();
        $request = $this->getRequest();
        $form    = $this->createForm(new JobType(), $entity);
        $form->bindRequest($request);
 
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
 
            $em->persist($entity);
            $em->flush();
 
            return $this->redirect($this->generateUrl('ens_job_preview', array(
            'company' => $entity->getCompanySlug(),
            'location' => $entity->getLocationSlug(),
            'token' => $entity->getToken(),
            'position' => $entity->getPositionSlug()
            )));
        }
 
        return $this->render('EnsJobeetBundle:Job:new.html.twig', array(
        'entity' => $entity,
        'form'   => $form->createView()
        ));
    }

    /**
    * Displays a form to edit an existing Job entity.
    *
    */
    public function editAction($token)
    {
        $em = $this->getDoctrine()->getEntityManager();
 
        $entity = $em->getRepository('EnsJobeetBundle:Job')->findOneByToken($token);
 
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Job entity.');
        }
 
        $editForm = $this->createForm(new JobType(), $entity);
        $deleteForm = $this->createDeleteForm($token);
 
        return $this->render('EnsJobeetBundle:Job:edit.html.twig', array(
        'entity'      => $entity,
        'edit_form'   => $editForm->createView(),
        'delete_form' => $deleteForm->createView(),
        ));
    }
 
    public function updateAction($token)
    {
        $em = $this->getDoctrine()->getEntityManager();
 
        $entity = $em->getRepository('EnsJobeetBundle:Job')->findOneByToken($token);
 
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Job entity.');
        }
 
        $editForm   = $this->createForm(new JobType(), $entity);
        $deleteForm = $this->createDeleteForm($token);
 
        $request = $this->getRequest();
 
        $editForm->bindRequest($request);
 
        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();
 
            return $this->redirect($this->generateUrl('ens_job_preview', array(
            'company' => $entity->getCompanySlug(),
            'location' => $entity->getLocationSlug(),
            'token' => $entity->getToken(),
            'position' => $entity->getPositionSlug()
            )));
        }
 
        return $this->render('EnsJobeetBundle:Job:edit.html.twig', array(
        'entity'      => $entity,
        'edit_form'   => $editForm->createView(),
        'delete_form' => $deleteForm->createView(),
        ));
    }
 
    public function deleteAction($token)
    {
        $form = $this->createDeleteForm($token);
        $request = $this->getRequest();
 
        $form->bindRequest($request);
 
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            $entity = $em->getRepository('EnsJobeetBundle:Job')->findOneByToken($token);
 
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Job entity.');
            }
 
            $em->remove($entity);
            $em->flush();
        }
 
        return $this->redirect($this->generateUrl('ens_job'));
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
        ->add('id', 'hidden')
        ->getForm()
        ;
    }
    
    public function extendAction($token)
    {
        $form = $this->createExtendForm($token);
        $request = $this->getRequest();
 
        $form->bindRequest($request);
 
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            $entity = $em->getRepository('EnsJobeetBundle:Job')->findOneByToken($token);
 
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Job entity.');
            }
 
            if (!$entity->extend()) {
                throw $this->createNotFoundException('Unable to find extend the Job.');
            }
 
            $em->persist($entity);
            $em->flush();
 
            $this->get('session')->setFlash('notice', sprintf('Your job validity has been extended until %s.', $entity->getExpiresAt()->format('m/d/Y')));
        }
 
        return $this->redirect($this->generateUrl('ens_job_preview', array(
        'company' => $entity->getCompanySlug(),
        'location' => $entity->getLocationSlug(),
        'token' => $entity->getToken(),
        'position' => $entity->getPositionSlug()
        )));
    }
 
    private function createExtendForm($token)
    {
        return $this->createFormBuilder(array('token' => $token))
        ->add('token', 'hidden')
        ->getForm()
        ;
    }
}
