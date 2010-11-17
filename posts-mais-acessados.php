<?php
/*
Plugin name: Posts Mais Acessados
Plugin URI: http://www.formacaogesac.mc.gov.br
Description: Posts mais acessados da Rede Social
Author: Equipe NSI - Instituto Federal Fluminense
Version: 1.0
Author URI: http://nsi.cefetcampos.br/
*/

if (basename($_SERVER['SCRIPT_NAME']) == basename(__FILE__)) exit('Please do not load this page directly');

/**
 *   Widget de Posts Mais Acessados
 */


class PostsMaisAcessados extends WP_Widget {
	function PostsMaisAcessados()
	{
		$widget_args = array('classname' => 'PostsMaisAcessados', 'description' => __( 'Posts Mais Acessados') );                
		parent::WP_Widget('PostsMaisAcessados', __('Posts Mais Acessados'), $widget_args);
                
	}

	function widget($args, $instance)
	{	global $wpdb;
		extract($args);
		$title = apply_filters('widget_title', empty($instance['title']) ? 'Posts Mais Acessados' : $instance['title']);
                $url_pagina = empty($instance['url_pagina']) ? 'url_pagina' : $instance['url_pagina'];
		$maxPages = empty($instance['maxPages']) ? 5 : $instance['maxPages'];    
                echo $before_widget;
		echo $before_title . $title . $after_title;
                ?>
                <ul class="mundo-digital hl">
			<?php $resultado = $wpdb->get_results("	SELECT *, DATE_FORMAT(post_date, '%d/%m/%Y') as data
								FROM ".$wpdb->prefix."postsmaisacessados
								ORDER BY pageviews DESC LIMIT $maxPages", OBJECT); ?>
				
			<?php foreach($resultado as $result) : the_post($result);?>
			   <li>
				<h3>    
					<a href="<?php echo $result->guid; ?>" ><?php echo $result->post_title; ?></a>
					<span class="date">	<?php the_author_meta('nickname', $result->post_author); ?></span>
				</h3>
				<p>   		
					<span class="date"><?php echo $result->data; ?></span>
					<?php limit_chars($result->post_content, 150); ?>
   				</p>
			   </li>
			<?php endforeach; ?>
		</ul>	 
	        <?php
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;
		
		if( $instance != $new_instance )
			$instance = $new_instance;
		
		return $instance;
	}

	function form($instance)
	{
	    	$title = esc_attr( $instance['title'] );
                $url_pagina = esc_attr( $instance['url_pagina'] );
	    	$maxPages = esc_attr( $instance['maxPages'] );
		
                ?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>">Título:</label>
			<input type="text" 
                               id="<?php echo $this->get_field_id('title'); ?>" 
                               name="<?php echo $this->get_field_name('title'); ?>" 
                               maxlength="26" value="<?php echo $title; ?>" 
                               class="widefat" 
                        />
		</p>
                
            	<p>
			<label for="<?php echo $this->get_field_id('maxPages'); ?>">Número máximo de posts:</label>
			<select id="<?php echo $this->get_field_id('maxPages'); ?>" 
                                name="<?php echo $this->get_field_name('maxPages'); ?>">
				<?php for($i=1; $i <= 10; $i++) : ?>
				    <option <?php if($maxPages == $i) echo 'selected="selected"'; ?> value="<?php if($i == 1) echo $i; else echo $i;?>">
                                        <?php if($i == 1) echo '1'; else echo $i; ?>          
				    </option>
	                	<?php endfor; ?>
			</select>
            	</p>
        	<?php
	}
}

/**
 * Carregando e registrando a widget Posts Mais Acessados
 */

function registra_widget() {
	register_widget('PostsMaisAcessados');
}
	

add_action('widgets_init', 'registra_widget');


/*
** Instalação/Desistalação do plugin de posts mais acessados
*/
class PluginPostsMaisAcessados {


	var $tabela = "postsmaisacessados";
		
	function inicializa_plugin () {
		add_filter('the_content', array("PluginPostsMaisAcessados","contarVisita"));
	}
	
	function instala_plugin () {
		global $wpdb;
		if($wpdb->get_var("SHOW TABLES LIKE '".$this->tabela()."'") != $this->tabela()) {
			$query = <<<END
			CREATE TABLE IF NOT EXISTS {tabela} (
			`id` int(10) NOT NULL AUTO_INCREMENT,
			`postid` int(10) NOT NULL,
			`blog_id` int(10) NOT NULL,
			`guid` varchar(255) NOT NULL DEFAULT '',
			`post_title` text NOT NULL,
			`post_author` bigint(20) unsigned NOT NULL DEFAULT '0',
			`post_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			`post_content` longtext NOT NULL,
 			`last_viewed` datetime default '0000-00-00 00:00',
			`pageviews` int(10) default '1',
			UNIQUE KEY id (`id`),
			UNIQUE KEY `guid` (`guid`)
			) AUTO_INCREMENT=1 ; 
END;
			$finalquery = $this->queryit($query);
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($finalquery);
		}
	
	}

	function queryit ($query){

		return str_replace("{tabela}",$this->tabela(),$query);

	}

	function tabela () {
		global $wpdb;
		return $wpdb->prefix.$this->tabela;
	}

	function contarVisita($post_texto){
		global $wpdb;
		$blog_url = get_bloginfo('url');
		$domain = "http://" . $_SERVER['SERVER_NAME'];
		$path = explode($domain, $blog_url);
		$path_blog = $path[1] . "/";
		$id = get_the_ID();
	
		$blogs = $wpdb->get_results("SELECT * FROM $wpdb->blogs where $wpdb->blogs.path = '$path_blog'", OBJECT);

		foreach ($blogs as $blog):
			if ($blog->blog_id == 1):
				$tabela_posts = $wpdb->posts;
			else:
				$tabela_posts = ("wp_".$blog->blog_id."_posts");
			endif;
			$resultado = $wpdb->get_results("SELECT *
							FROM $tabela_posts
							WHERE $tabela_posts.post_status = 'publish'
							AND $tabela_posts.post_type = 'post'
							AND $tabela_posts.ID = $id;					
							", OBJECT); 
			foreach($resultado as $result):
				
				$sql = "INSERT INTO wp_postsmaisacessados (postid, blog_id, guid, post_title, post_author, post_date, post_content, last_viewed, pageviews) VALUES ('".$result->ID."', '".$blog->blog_id."', '".$result->guid."', '".$result->post_title."', '".$result->post_author."', '".$result->post_date."', '".$result->post_content."', sysdate(), 1) ON DUPLICATE KEY UPDATE post_title = '".$result->post_title."', post_content = '".$result->post_content."', last_viewed = sysdate(), pageviews=pageviews+1";

				$wpdb->query($sql) or die(mysql_error());
					
			endforeach;					

		endforeach;	

		return $post_texto;
	}	
}
$plugin_pma = new PluginPostsMaisAcessados();
register_activation_hook(__FILE__, array(&$plugin_pma ,'instala_plugin') );
add_action('init',array(&$plugin_pma,'inicializa_plugin'));
?>
