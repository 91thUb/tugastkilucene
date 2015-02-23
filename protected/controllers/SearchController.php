<?php

class SearchController extends Controller
{
	public $moduleName = 'Search';
    public $layout = '//layouts/search';
	
	public function actionIndex()
	{
        if(isset($_POST['q']) && strlen($_POST['q']) > 0)
        {
            $keyword = $_POST['q'];
            
            $debug = strpos($keyword, "--debug")? true : false;
            
            if($debug)
            {
                $keyword = trim(preg_replace("/--debug/", "", $keyword));
            }

            $results = $this->search($keyword);
            
            $error =  "-";
            $warning =  "-";
            $status =  "-";
            $total = "-";
            $total_found =  "-";
            $time = "-";
            $words =  "-";
            $matches =  "-";
            
            foreach($results as $result)
            {
                $result->content = $this->truncate($result->content, 200);
            }
            
            $this->render('index', array(
                'error' => $error, 
                'warning' => $warning,
                'status' => $status,
                'total' => $total,
                'total_found' => $total_found,
                'time' => $time,
                'words' => $words,
                'results' => $results,
                'query' => $keyword,
                'debug' => $debug,
                'matches' => $matches
                )
            );
        }
        else
        {
            $this->render('index');
        }
	}
    
    private function extractIds($results)
    {
        if(!(count($results) > 0)) return false;
        
        $result = array();
        
        foreach($results as $id => $val)
        {
            $result[] = $id;
        }
        
        return $result;
    }
    
    //truncate a string only at a whitespace (by nogdog)
    private function truncate($text, $length) 
    {
        $length = abs((int)$length);
        
        if(strlen($text) > $length) 
        {
            $text = preg_replace("/^(.{1,$length})(\s.*|$)/s", '\\1...', $text);
        }
        
        return($text);
    }
    
    private function search($terms)
    {
        Yii::import('application.vendor.*');
        require_once('Zend/Search/Lucene.php');
        
        $index = new Zend_Search_Lucene(Yii::getPathOfAlias('application.index'));
        $result = $index->find($terms);
        
        $query = Zend_Search_Lucene_Search_QueryParser::parse($terms);
        
        return $result;
    }
}