<?php 

namespace App\Controller;

use App\Controller\UploadCon;
use CURLFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController {

  private $upload_obj;

  public function __construct() {
    $this->upload_obj = new UploadCon();
  }

  #[Route('/', name: 'app_home')]
  public function home(Request $request): Response {
    $file = $request->request->all();
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      $upload = $this->upload_obj->uploadImage($_POST);
      $image = $upload;
      $food_name = [];
      $probability = [];

      // Resizing the image .

      // Set the maximum allowed image size
      $max_size = 720;

      // Get the image file path
      $image_path = $image;

      // Get the image size
      list($width, $height) = getimagesize($image_path);
      $extension = pathinfo($image_path, PATHINFO_EXTENSION);

      // Calculate the new image size while maintaining aspect ratio
      if ($width > $height) {
          $new_width = $max_size;
          $new_height = $max_size;
          // $new_height = intval($height * ($max_size / $width));
      } else {
          $new_width = $max_size;
          // $new_width = intval($width * ($max_size / $height));
          $new_height = $max_size;
      }

      // Create a new image from the file
      if($extension == 'jpeg' || $extension == 'jpg') {
        $image = imagecreatefromjpeg($image_path);
      }
      else if ($extension == 'png') {
        $image = imagecreatefrompng($image_path);
      }

      // Create a new blank image with the new size
      $new_image = imagecreatetruecolor($new_width, $new_height);

      // Copy and resize the original image to the new image
      imagecopyresized($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

      // Save the new image as a JPEG with quality 90
      imagejpeg($new_image, $upload, 90);

      // Free up memory
      imagedestroy($image);
      imagedestroy($new_image);

      // API call.
      $curl = curl_init();

      curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api.logmeal.es/v2/image/segmentation/complete',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      // CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,Binary,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => array('image'=> new CURLFILE($upload)),
      CURLOPT_HTTPHEADER => array(
        'Authorization: Bearer 38859f9c604f7a7b0bac43a65c6f3b5c19f05280'
      ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    // $res = json_encode($response);
    // print_r($response->foodFamily);
    
    $res2 = json_decode($response);

    echo '<pre>';
    print_r($res2->segmentation_results['0']);
    echo '</pre>';

    if($res2) {
    // Pred 1
    if(!isset($res2->segmentation_results['0']->recognition_results['0']->subclasses['0'])) {
      $food_name[] = $res2->segmentation_results['0']->recognition_results['0']->name;
      $probability[] = $res2->segmentation_results['0']->recognition_results['0']->prob;
      $food_name[] = $res2->segmentation_results['0']->recognition_results['1']->name;
      $probability[] = $res2->segmentation_results['0']->recognition_results['1']->prob;
      $food_name[] = $res2->segmentation_results['0']->recognition_results['2']->name;
      $probability[] = $res2->segmentation_results['0']->recognition_results['2']->prob;
      $food_name[] = $res2->segmentation_results['0']->recognition_results['3']->name;
      $probability[] = $res2->segmentation_results['0']->recognition_results['3']->prob;
    
    }
    else {
      $food_name[] = $res2->segmentation_results['0']->recognition_results['0']->subclasses['0']->name;
      $probability[] = $res2->segmentation_results['0']->recognition_results['0']->subclasses['0']->prob;
      
      // Pred 2
      $food_name[] = $res2->segmentation_results['0']->recognition_results['0']->subclasses['1']->name;
      $probability[] = $res2->segmentation_results['0']->recognition_results['0']->subclasses['1']->prob;
      
      // Pred 3
      $food_name[] = $res2->segmentation_results['0']->recognition_results['0']->subclasses['2']->name;
      $probability[] = $res2->segmentation_results['0']->recognition_results['0']->subclasses['2']->prob;
      
      // Pred 3
      $food_name[] = $res2->segmentation_results['0']->recognition_results['0']->subclasses['3']->name;
      $probability[] = $res2->segmentation_results['0']->recognition_results['0']->subclasses['3']->prob;
      
      // Pred 4
      $food_name[] = $res2->segmentation_results['0']->recognition_results['0']->subclasses['4']->name;
      $probability[] = $res2->segmentation_results['0']->recognition_results['0']->subclasses['4']->prob;
      echo '<pre>';
      print_r($res2->segmentation_results['0']->recognition_results);
      echo '</pre>';
    }
    }
    }

    $data  = 'Welcome to FoodZilla!!';
    return $this->render('FoodZilla/home.html.twig', ['data' => $data, 'f_name' => $food_name, 'prob' => $probability]);
    

  }


}