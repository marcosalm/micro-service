<?php
/**
 * Created by PhpStorm.
 * User: marcos.almeida-pit
 * Date: 02/10/2015
 * Time: 09:46
 */

namespace MSIC\DAO;


class KrotonService
{

    private $wsdl = "http://hadmsvbdmdm01.pitagoras.apollo.br:8003/ProcessoSeletivo/V1?wsdl";
    private $login  = 'AplicVestibulares';
    private $pass   = 'Vest2015';
    protected $cursos;
    protected $localidade;


    protected function get_header() {

        $headers = '<wsse:Security soapenv:mustUnderstand="1" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
                         <wsse:UsernameToken wsu:Id="UsernameToken-8C654B52CD2D72F8B914371350534441">
                            <wsse:Username>AplicVestibulares</wsse:Username>
                            <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">Vest2015</wsse:Password>
                         </wsse:UsernameToken>
                      </wsse:Security>';
        return $headers;
    }


    protected function serviceCall($service,$params = array()){
        try {
            $client = new soapclient($this->wsdl, TRUE);
            $client->setHeaders($this->get_header());

            if ($err = $client->getError()) {
                return array();
                exit();
            }

            $result = $client->call($service, $params);
            if ($client->fault) {
                $return = false;
            } elseif ($client->getError()) {
                $return = false;
            } else {
                $return = $result;
            }

            return $return;
        }
        catch (Exception $e){

            return array();

        }
    }

    function getUF($curso = '', $pais = '55', $ativo = 'sim') {

        if (!empty($curso) || $curso != " " || $curso != ""){
            $params = array("codigoPais"=> "1", "processoSeletivoAtivo"=> "true" ,"nomeCurso"=>$curso);
            $result = $this->serviceCall('consultaEstadosPorCurso',$params);
        } else {
            $result = $this->serviceCall('consultaEstados',array("codigoPais"=> "", "processoSeletivoAtivo"=> true ));
        }

        foreach ($result as $value) {
            $retornoUF[] = $value['uf'];
        }

        if (count($retornoUF) > 0) {
            return array_unique($retornoUF);
        }
        return array();
    }

    function getCidade($estado, $curso = '', $pais = '55', $ativo = 'sim') {
        if ($curso != ''){
            $params = array("codigoEstado"=>$estado ,"codigoPais"=> null, "processoSeletivoAtivo"=> true,"nomeCurso"=>$curso);
            $result = $this->serviceCall('consultaCidadesPorEstado',$params);
        } else {
            $params = array("codigoEstado"=>$estado ,"codigoPais"=> null, "processoSeletivoAtivo"=> true);
            $result = $this->serviceCall('consultaCidadesPorCurso',$params);
        }

        foreach ($result as $value) {
            $retornoCidades[] = $value['cidade'];
        }

        if (count($retornoCidades) > 0) {
            return array_unique($retornoCidades);
        }

        return array();
    }

    function getUnidade($municipio = '', $curso = '') {

        if(empty($curso)) {
            $params = array("codigoCidade" => $municipio);
            $result = $this->serviceCall('consultaUnidadesPorCidade', $params);
        }else {
            $params = array("codigoCidade" => $municipio, "curso" => $curso);
            $result = $this->serviceCall('consultaUnidadesPorCurso', $params);
        }

        foreach ($result as $value) {

            $dadoCamp = $this->getDataUnidadeOrbitado($value['unidade']['codigo']);

            $arrayUnidade[] = array('codigo' => $value['unidade']['codigo'],
                'descricao' => $value['unidade']['nome'],
                'cidade' => $value['unidade']['endereco']['cidade'],
                'endereco' => $dadoCamp->endereco,
                'telefone1' => $dadoCamp->telefone,
                'telefone2' => '');
        }


        if (!empty($arrayUnidade)) {
            return $arrayUnidade;
        }

        return array();
    }

    function getCurso($campus = ''){
        if($campus !=''){
            $this->localidade = $campus;
        }
        $params = array("codigoUnidade" => $campus);

        $this->cursos = $this->serviceCall('consultaCursosDisponiveis', $params);

        if(!empty($this->cursos)) {
            foreach ($this->cursos as $rows) {
                $arrayCursos[] = array('codigoCurso' => $rows["curso"]['codigo'],
                    'curso' => $rows["curso"]['nome']);
                //
            }
            return $arrayCursos;
        }
        return array();
    }

    function getTurnos($campus, $curso) {

        if(isset($this->localidade) && $this->localidade === $campus){
            foreach ($this->cursos as $especialidade) {

                if (strpos($especialidade["curso"]['nome'], $curso) !== false) {

                    $params = array("codigoCurso" => $especialidade["curso"]['codigo']);
                    $result = $this->serviceCall('consultaDadosCurso', $params);

                    $arrayPresencial[] = array('codigoCurso' => $especialidade["curso"]['codigo'],
                        'curso' => $curso,
                        'turno' => $result['turno'],
                        'fies' => $result['bolsas'],
                        'exibirpreco' => $result['exibirPreco'],
                        'moedareal' => $result['valorMensalidade'],
                        'unidade' => $campus);

                }
            }
        } else{
            $this->localidade = $campus;

            $result = $this->getCurso($campus);
            foreach ($result as $especialidade) {

                if (strpos($especialidade["curso"]['nome'], $curso) !== false) {

                    $params = array("codigoCurso" => $especialidade["curso"]['codigo']);
                    $result = $this->serviceCall('consultaDadosCurso', $params);

                    $arrayPresencial[] = array('codigoCurso' => $especialidade["curso"]['codigo'],
                        'curso' => $curso,
                        'turno' => $result['turno'],
                        'fies' => $result['bolsas'],
                        'exibirpreco' => $result['exibirPreco'],
                        'moedareal' => $result['valorMensalidade'],
                        'unidade' => $campus);

                }
            }
        }
        if (isset($arrayPresencial)){
            return $arrayPresencial;
            exit;
        }
        return array();
    }

    public function codigoCurso($campus,$nomeCurso, $turno){

        $params = array("codigoUnidade" => $campus,"turnoCurso"=>$turno, "curso"=>$nomeCurso);
        $result = $this->serviceCall('consultaCodigoCurso', $params);

        if (!empty($result)) {
            return $result;
        }
        return false;
    }

    public function getDataHoraProva($campus, $curso, $turno) {

        $params = array("codigoUnidade" => $campus,"turno"=>$turno, "curso"=>$curso);
        $result = $this->serviceCall('consultaDatasDeProva', $params);

        if (!empty($result)) {
            return $result;
        }
        return false;
    }

    function getCandidatoStatus($cpf) {

        $params = array("cpf" => $cpf);
        $result = $this->serviceCall('consultaInscricaoPorCPF', $params);

        if (!empty($result)) {
            return $result;
        }
        return false;

    }

    /**
     * @param $codUnidadeKroton
     * Fun??o para carregar as informa??es referente as Unidades que n?o s?o guardadas no Olimpo
     * (Endere?o, Telefone, CodigoUnidade[FAV,FAC, etc...])
     */
    function getDataUnidadeOrbitado($codUnidadeKroton){

        if(preg_match('/sandbox.tiweb/i',$_SERVER['HTTP_HOST']))
            $dirJSON = "/data/testing/json/vestibulares/localidades/localidades.json";
        elseif(preg_match('/homologa.vestibulares.br/i',$_SERVER['HTTP_HOST']))
            $dirJSON = "/data/production/json/vestibulares/localidades/localidades.json";
        else
            $dirJSON = "/data/production/json/vestibulares/localidades/localidades.json";


        $json_string = file_get_contents($dirJSON);
        $data = json_decode($json_string);

        foreach($data->g_vest_loc as $info){
            if($info->codigo_olimpo === $codUnidadeKroton){
                $result = $info;
            }
        }

        if(isset($result)){
            return $result;
        }
        return false;
    }


    /**
     * @param $codInscricao
     * @return array|bool
     * Carrega as informa??es do candidato Inscrito.
     */
    function getCandidatoInscricao($codInscricao) {

        $params = array("codigoInscricao" => $codInscricao);
        $result = $this->serviceCall('consultaCandidatoInscricao', $params);

        $cursoprimeiraOpcao_cod = $result['codigoCursoPrimeiraOpcao'];
        $cursoSegundaOpcao_cod = $result['codigoCursoSegundaOpcao'];

        $cursoprimeiraOpcao = $this->listaDadosEspecialidade($cursoprimeiraOpcao_cod);
        $cursoSegundaOpcao = $this->listaDadosEspecialidade($cursoSegundaOpcao_cod);

        $aux = explode('-', $cursoprimeiraOpcao['nome']);
        $aux2 = explode('-', $cursoSegundaOpcao['nome']);

        $dataP = $this->getDataHoraProva($result['Campus'], trim($aux[0]), trim($aux[1]));

        foreach ($dataP as $data){
            if($data['codigoConcurso'] === $result['codigo']){
                $dataProva = $data;
            }
        }

        $dadosCampus = $this->getDataUnidadeOrbitado($result['Campus']);

        if($result){

            $arrayDadosCandidato =  array(
                'CodigoInscricao' => $result['CodigoInscricao'],
                'CPF' =>$result['CPF'],
                'Nome' => $result['Nome'],
                'Email' => $result['Email'],
                'TelefonePrincipal' => $result['TelefonePrincipal'],
                'RG' => $result['RG'],
                'DataNascimento' => $result['DataNascimento'],
                'NecessidadesEspeciais' => $result['NecessidadesEspeciais'],
                'Sexo' => $result['Sexo'],
                'TelefoneResidencial' => $result['TelefoneResidencial'],
                'CEP' => $result['CEP'],
                'Endereco' => $result['Endereco'],
                'EnderecoBairro' => $result['EnderecoBairro'],
                'EnderecoEstado' => $result['EnderecoEstado'],
                'EnderecoMunicipio' => $result['EnderecoMunicipio'],
                'EnderecoArea' => $result['EnderecoArea'],
                'DataInscricao' => $result['DataInscricao'],
                'TipoEscolaridade' => $result['TipoEscolaridade'],
                'TipoEnem' => $result['TipoEnem'],
                'NumeroInscricaoEnem' => $result['NumeroInscricaoEnem'],
                'EscolaEstado' => $result['NoEscolaEstadome'],
                'EscolaMunicipio' =>$result['EscolaMunicipio'],
                'Escola' => $result['Escola'],
                'EscolaAno' => $result['EscolaAno'],
                'SituacaoProfissional' => $result['SituacaoProfissional'],
                'ProcessoSeletivo' => $result['ProcessoSeletivo'],
                'ProcessoSeletivoEstado'=> $result['ProcessoSeletivoEstado'],
                'Campus' => $result['Campus'],
                'Curso' => $aux[0],
                'Campus2' => $result['Campus2'],
                'Curso2' => $aux2[0],
                'Treineiro' => $result['Treineiro'],
                'LocalProva' => $result['LocalProva'],
                'Consultor' => $result['Consultor'],
                'ComoConheceu' => $result['ComoConheceu'],
                'CanalDeVendas' => $result['CanalDeVendas'],
                'CodigoTitulo' => $result['CodigoTitulo'],
                'Voucher' => $result['Voucher'],
                'EstadoCivil' => $result['EstadoCivil'],
                'CodigoConvenio' => $result['CodigoConvenio'],
                'DescricaoLocalidade' => $dadosCampus['descricao'],
                'cidadeunidade' => $dadosCampus['cidade'],
                'estadounidade' => $result['EnderecoEstado'],
                'ruaunidade' =>$dadosCampus['endereco'],
                'dataProva' => $dataProva['dataProva'],
                'horaProva' => $dataProva['horaProva'],
                'descricaoturno'=> $aux[1],
                'descricaoturno2'=> $aux2[1]

            );
            return $arrayDadosCandidato;
        } else{
            return false;
        }
    }

    function getConvenios($campus){
        $params = array("codigoConvenio" => $campus);
        $result = $this->serviceCall('consultaConvenios', $params);

        if (!empty($result)) {
            return $result;
        }
        return false;
    }

    function getLocalDeProva($codigoConcurso){
        $params = array("codigoProcessoSeletivo" => $codigoConcurso, "modalidade"=>"");
        $result = $this->serviceCall('consultaLocaisDeProva', $params);

        if (!empty($result)) {
            return $result;
        }
        return false;
    }

    function InsertOrUpdate($dados){

        $q_user = $this->getCandidatoStatus($dados['cpf']);
        $params = $dados;
        if($q_user){
            $update = $this->serviceCall('atualizaInscricao', $params);
            return $update;
        } else {
            $insert = $this->serviceCall('efetuaInscricao', $params);
            if($insert){
                return true;
            } else {
                return false;
            }
        }


    }

    function insertLogConsultores($dados){
        $data = new Dados();

        return $data->inserirLogConsultores($dados);

    }

    function atualizaLogConsultores($dados){
        print_r($dados);

    }


    public function listaDadosEspecialidade($CodigoEspecialidade)
    {
        $params = array("codigoCurso" => $CodigoEspecialidade);
        $result = $this->serviceCall('consultaDadosCurso', $params);

        if (!empty($result)) {
            return $result;
        }
        return array();
    }

    public function listaConcurso( $curso)
    {
        $params = array("codigoCurso" => $curso, "modalidade" => "");
        $result = $this->serviceCall('consultaProcessoSeletivo', $params);


        return $result;

    }

}