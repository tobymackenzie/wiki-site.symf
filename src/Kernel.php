<?php
namespace TJM\WikiSite;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Kernel as Base;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends Base{
	use MicroKernelTrait;

	protected $config = WikiSite::CONFIG_DIR . '/config.yml';
	protected $debug;
	protected $environment = 'dev';
	protected $bundlesPath;
	protected $projectDir;
	protected $routes = WikiSite::CONFIG_DIR . '/routing.yml';

	public function __construct($opts = null){
		if($opts){
			if(!is_array($opts)){
				$opts = [
					'config'=> $opts,
				];
			}
			foreach($opts as $opt=> $value){
				$setter = 'set' . ucfirst($opt);
				if(method_exists($this, $setter)){
					$this->$setter($value);
				}else{
					$this->$opt = $value;
				}
			}
		}
		//--default to dev + debug in cli
		//-# ensures config changes are automatically handled
		if(!isset($this->environment)){
			$this->environment = (php_sapi_name() === 'cli' ? 'dev' : 'prod');
		}
		if(!isset($this->debug)){
			$this->debug = $this->environment !== 'prod';
		}
	}
	protected function configureContainer(ContainerConfigurator $conf, LoaderInterface $loader){
		if($this->config){
			$conf->import($this->config);
		}else{
			parent::configureContainer($conf, $loader);
		}
	}
	protected function configureRoutes(RoutingConfigurator $conf){
		if($this->routes){
			$conf->import($this->routes);
		}else{
			parent::configureRoutes($conf);
		}
	}
	private function getBundlesPath(){
		return $this->bundlesPath ?: $this->getConfigDir() . '/bundles.php';
	}
	public function getProjectDir(): string{
		if(empty($this->projectDir)){
			$this->projectDir = __DIR__ . '/..';
		}
		return $this->projectDir;
	}
	public function run(?Request $request = null){
		if(empty($request)){
			if(php_sapi_name() === 'cli'){
				//--for dev, use argument as path
				$path = isset($GLOBALS['argv']) && isset($GLOBALS['argv'][1]) ? $GLOBALS['argv'][1] : '/';
				if(substr($path, 0, 1) !== '/'){
					$path = "/{$path}";
				}
				$request = Request::create($path, 'GET');
			}else{
				$request = Request::createFromGlobals();
			}
		}
		$response = $this->handle($request);
		$response->send();
		$this->terminate($request, $response);
	}
}
