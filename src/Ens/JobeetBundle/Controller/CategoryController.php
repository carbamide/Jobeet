<?php

namespace Ens\JobeetBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Ens\JobeetBundle\Entity\Category;

class CategoryController extends Controller
{
    public function showAction($slug, $page)
    {
        $em = $this->getDoctrine()->getEntityManager();
        
        $category = $em->getRepository('EnsJobeetBundle:Category')->findOneBySlug($slug);
        
        if (!$category) {
            throw $this->createNotFoundException('Unable to find Category entity.');
        }
        
        $total_jobs = $em->getRepository('EnsJobeetBundle:Job')->countActiveJobs($category->getId());
        $jobs_per_page = $this->container->getParameter('max_jobs_on_category');
        $last_page = ceil($total_jobs / $jobs_per_page);
        $previous_page = $page > 1 ? $page - 1 : 1;
        $next_page = $page < $last_page ? $page + 1 : $last_page;
 
        $category->setActiveJobs($em->getRepository('EnsJobeetBundle:Job')->getActiveJobs($category->getId(), $jobs_per_page, ($page - 1) * $jobs_per_page));
 
        return $this->render('EnsJobeetBundle:Category:show.html.twig', array(
        'category' => $category,
        'last_page' => $last_page,
        'previous_page' => $previous_page,
        'current_page' => $page,
        'next_page' => $next_page,
        'total_jobs' => $total_jobs
        ));
    }
    
    public function createAction()
    {
        $entity = new Job();
        $request = $this->getRequest();
        $form = $this->createForm(new JobType(), $entity);
        $form->bindRequest($request);
        
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            
            $em->persist($entity);
            $em->flush();
            
            return $this->redirect($this->generateUrl('ens_job_show', array(
            'company' => $entity->getCompanySlug(),
            'location' => $entity->getLocationSlug(),
            'id' => $entity->getId(),
            'position' => $entity->getPositionSlug()
            )));
        }
        
        return $this->render('EnsJobeetBundle:Job:new.html.twig', array(
        'entity' => $entity,
        'form' => $form->createView()
        ));
    }
}