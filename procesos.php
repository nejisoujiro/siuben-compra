<?php

setlocale(LC_ALL,"es_ES");

// Cambia esto por la ruta real del archivo class-renderer.php
require_once __DIR__ . '/renderer/class-renderer.php';

function fetchData($url, $bearerToken = null){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 20);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
	    "Authorization: Bearer $bearerToken",
	    "Content-Type: application/json"
	]);
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}

function get_cache_path() {
	return __DIR__ . '/cache/data.json';
}

function cache_valido($path, $horas = 3) {
	return file_exists($path) && (time() - filemtime($path) < $horas * 3600);
}

function obtener_datos_api($codigo_uc = '000195') {
	$cache_path = get_cache_path();

	if (cache_valido($cache_path)) {
		$json = file_get_contents($cache_path);
		$data = json_decode($json, true, 512, JSON_BIGINT_AS_STRING);
		if ($data) return $data;
	}

	$url = 'https://datosabiertos.dgcp.gob.do/api-dgcp/v1/procesos/agrupados?unidad_compra=' . intval($codigo_uc);

	$data = fetchData($url);

	if (!$data) return null;

	file_put_contents($cache_path, $data);

	return json_decode($data, true, 512, JSON_BIGINT_AS_STRING);
}

function mostrarProcesos() {
	$data = obtener_datos_api('000195'); // Puedes cambiar el código

	if ($data) {
		$dgcp = new DGCP_Renderer($data);
		$html = $dgcp->render();
		$html .= '<p class="pt-2 small">* Esta página se actualiza cada día por parte de la <a href="https://www.dgcp.gob.do/" target="_blank">Dirección General de Contrataciones Públicas (DGCP)</a>.</p>';
		return $html;
	} else {
		return "<strong>Esta institución no ha publicado procesos de compra en el SECP.</strong>";
	}
}
?>
