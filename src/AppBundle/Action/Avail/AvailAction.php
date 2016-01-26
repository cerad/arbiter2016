<?php
namespace AppBundle\Action\Avail;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class AvailAction extends Controller
{
    private $template;
    private $loader;
    private $reporter;

    public function __construct($template, AvailLoaderExcel $loader, AvailReporterExcel $reporter)
    {
        $this->template = $template;
        $this->loader   = $loader;
        $this->reporter = $reporter;
    }
    public function __invoke(Request $request)
    {
        $fb = $this->createFormBuilder();
        $fb->add('file',FileType::class,['label' => 'Avail File']);
        $fb->add('generate', SubmitType::class, ['label' => 'Generate Report']);
        $form = $fb->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            /** @var UploadedFile $file */
            $file = $form['file']->getData();
            $name = $file->getClientOriginalName();
            $path = $file->getRealPath();

            $loaderResults = $this->loader->load($path,$name);

            $reporter = $this->reporter;

            $reporter->report($loaderResults->dates,$loaderResults->officials);

            $outFilename = 'Availability-' . date('Ymd-Hi') . '.' . $reporter->getFileExtension();

            $headers = [
                'Content-Type'        => $reporter->getContentType(),
                'Content-Disposition' => sprintf('attachment; filename="%s"',$outFilename),
            ];

            $response = new Response($reporter->getContents(),201,$headers);

            return $response;

            // die(sprintf("Uploaded %s %s %d",$name,$path,count($loaderResults->officials)));
        }

        return $this->render(
            $request->get('_template'),
            ['form' => $form->createView()]
        );
        //return $this->render($this->template,[]);
        //return $this->render('@Arbiter/Avail/AvailIndex.html.twig',[]);
    }
}
