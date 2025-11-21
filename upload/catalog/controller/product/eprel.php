<?php
class ControllerProductEprel extends Controller {

    public function fiche() {

        $apiKey = 'EJIHIAziLs86nUV0rs7V08u5XTg1m8LF16uXJ9J4'; // <-- upiši svoj API KEY

        if (!isset($this->request->get['category']) || !isset($this->request->get['model'])) {
            exit('Missing parameters');
        }

        $category = $this->request->get['category'];
        $model    = $this->request->get['model'];

        $url = "https://eprel.ec.europa.eu/api/products/$category/$model/fiche?language=HR";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $apiKey
        ]);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code == 200) {

            header("Content-Type: application/pdf");
            header("Content-Disposition: inline; filename=\"Fiche_$model"."_HR.pdf\"");
            echo $response;

        } else {
            echo "EPREL Error: HTTP $code<br>";
            echo $response;
        }
    }
}
