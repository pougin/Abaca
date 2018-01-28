<?php
###############################
# Press 一个相对简单的新闻发布类
# $Article	文章信息数组，必要index：title标题，p段落数组/body正文，type文章类型
#	p[$i]=array(type,text)type段落类型，normal普通，subtitle小标题，image图片，imgdesc图片说明；text，段落的内容
#	如果有index body，则直接用body作为文章正文，否则用p生产文章正文
# $index	是否建立搜索索引，默认true
# $returnHtml	是否返回文章的html，默认false
# by pougin
###############################

class Press {
	//templete file path
	private $templete;
	private $localPath;
	private $remotePath;
	private $db;
	private $articleType=array(
		'huoche'=>1,//火车资讯
		'tianqi'=>2,//天气资讯
	);
	
	function __construct($file){
		$this->templete=$file;
		$this->db=DB::newMysqli('press');
	}
	
	//设置模板文件（html+php tag混排的）
	public function setTemplete($file){
		$this->templete=$file;
		return $this;
	}
	
	//设置静态文件的存储位置
	public function setLocalPath($dir){
		$this->localPath=rtrim($dir,'/');
		return $this;
	}
	
	//设置web访问的链接地址，只包含目录
	public function setRemotePath($uri){
		$this->remotePath=rtrim($uri,'/');
		return $this;
	}
	
	//在press数据表中请求唯一id
	private function getPressId(){
		$now=time();
		$data['addtime']=$now;
		$data['modifytime']=$now;
		$this->db->insert('article',$data);
		$articleId=$this->db->insert_id;
		return $articleId;
	}
	
	//生产文章的索引，包括分词，添加数据库记录。建立索引等待sphinx自动进行
	//$article文章id号，$uri文章地址，$data文章数据
	private function makeIndex($articleId,$uri,$data){
		//添加处理索引的功能
		$body='';
		if (isset ( $data ['p'] )) {
			foreach ( $data ['p'] as $v ) {
				$body = $v ['text'] . ' ';
			}
		} else {
			$body = strip_tags ( $data ['body'] );
		}
		$info=array('id'=>$articleId,
			'uri'=>$uri,
			'title'=>Func::wordSegment($data['title']),
			'fulltext'=>Func::wordSegment($body),
			'type'=>$data['type']
		);
		$this->db->insert('fulltext',$info);
	}
	
	//生产文章的正文部分，返回字符串
	//$p 文章的段落数组
	private function makeBody($p){
		$R='';$imgDesc='';
		while (($v=array_pop($p))!==false){
			switch ($v['type']) {
				case 'subtitle':
					$R='<b class="pressSubtitle">'.$v['text'].'</b>'.$R;
				break;
				case 'image':
					$alt=empty($imgDesc)?'':' alt="'.$imgDesc.'"';
					$R='<div class="pressImage"><img src="'.$v['text'].'"'.$alt.'></div>'.$R;
				break;
				case 'imgdesc':
					$imgDesc=$v['text'];
					$R='<div class="pressImgdesc">'.$v['text'].'</div>'.$R;
				break;
				default:
					$R='<p>'.$v['text'].'</p>'.$R;
				break;
			}
		}
		return $R;
	}
	
	/**
	 * 发布文章
	 *
	 * @param Array $Article
	 * @param Interger $status，文章发布的状态，默认直接发布
	 * @param Boolean $index，是否索引文章，默认是
	 * @param Boolean $returnHtml，是否返回文章生产好的html，默认否
	 */
	public function pressArticle($Article,$status=0,$index=true,$returnHtml=false){
		if (!file_exists($this->templete)) {
			return array('status'=>0,'message'=>'文章模板加载失败');
		}
		if (!is_dir($this->localPath)) {
			return array('status'=>0,'message'=>'文章的输出地址不可用');
		}
		$articleId=$this->getPressId();
		if (empty($articleId)) {
			return array('status'=>0,'message'=>'请求文章唯一编号失败');
		}
		if (!isset($Article['body'])) {
			$Article['body']=$this->makeBody($Article['p']);
		}
		ob_start();
		include $this->templete;
		$html=ob_get_clean();
		if (empty($html)) {
			return array('status'=>0,'message'=>'生产文章内容失败');
		}
		$data['html']=$html;
		$relativePath='/'.date('Y').'/'.date('md').'/'.$articleId.'.html';
		$data['local']=$this->localPath.$relativePath;
		$data['remote']=$this->remotePath.$relativePath;
		$data['addIntoSearch']=$index?'Y':'N';
		//写出文件并添加到数据库
		if ($status==0) {
			file_put_contents($data['local'],$html);;
		}
		$this->db->update('press',$data,"id='$articleId' LIMIT 1");
		//索引的处理
		if ($index) {
			$Article['type']=$this->articleType[$Article['type']];
			$this->makeIndex($articleId,$uri,$Article);
		}

		if ($returnHtml) {
			return array('status'=>1,'message'=>'Article Id:'.$articleId.' 文章发布成功','html'=>$html);
		}else{
			return array('status'=>1,'message'=>'Article Id:'.$articleId.' 文章发布成功');
		}
	}
	
	//添加其他文章管理的方法
}


























?>