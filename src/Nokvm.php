<?php

namespace Lambq\Nokvm;

use GuzzleHttp\Client;

class Nokvm
{
    protected $url;
    protected $key;

    public function __construct(string $url, string $key)
    {
        $this->key = $key;
        $this->url = $url;
    }
    // 数组签名
    public function sign()
    {
        $array = [
            'time'      => time(),
            'random'    => rand(100000, 999999),
            'signature' => '',
        ];
        $data['timeStamp']  = $array['time'];
        $data['randomStr']  = $array['random'];
        $data['token']      = $this->key;    //token 自行配置的令牌，不清楚可看概述章节。
        sort($data,SORT_STRING);
        $str = implode($data);
        $array['signature'] = strtoupper(md5($str));
        return $array; //最终得到加密后全大写的签名
    }
    // 字符串签名
    public function str_sign()
    {
        $array = [
            'time'      => time(),
            'random'    => rand(100000, 999999),
            'signature' => '',
        ];
        $data['timeStamp']  = $array['time'];
        $data['randomStr']  = $array['random'];
        $data['token']      = $this->key;    //token 自行配置的令牌，不清楚可看概述章节。
        sort($data,SORT_STRING);
        $str = implode($data);
        $array['signature'] = strtoupper(md5($str));
        return '?time='.$array['time'].'&random='.$array['random'].'&signature='.$array['signature']; //最终得到加密后全大写的签名
    }
    // 发送curl请求
    public function getUrl($action, $url, array $array = [])
    {
        $client     = new Client();
        $response   = '';
        $url        = $this->url.$url;
        switch ($action)
        {
            case 'get':
                $response = $client->request('GET', $url, [
                    'query' => $this->sign(),
                ]);
                break;
            case 'put':
                $response = $client->request('PUT', $url, [
                    'query' => $this->sign(),
                    'form_params' => $array,
                ]);
                break;
            case 'delete':
                $response = $client->request('DELETE', $url, [
                    'query' => $this->sign(),
                    'form_params' => $array,
                ]);
                break;
            case 'post':
                $response = $client->request('POST', $url, [
                    'query'  => $this->sign(),
                    'form_params'  => $array,
                ]);
                break;

        }
        $code = $response->getStatusCode();
        if ($code == 200)
        {
            $data   = json_decode($response->getBody(), true);
            if ($data['code'] == 0)
            {
                if (array_key_exists('data', $data))
                {
                    return [
                        'code'  => $data['code'],
                        'msg'   => str_replace('虚拟机', '云服务器', $data['message']),
                        'data'  => $data['data']
                    ];
                } else {
                    return [
                        'code'  => $data['code'],
                        'msg'   => str_replace('虚拟机', '云服务器', $data['message']),
                    ];
                }
            } else {
                return [
                    'code'  => $data['code'],
                    'msg'   => str_replace('虚拟机', '云服务器', $data['message']),
                ];
            }
        } else {
            return [
                'code'  => 1,
                'msg'   => '云服务器出现接口问题！请联系管理……',
            ];
        }

    }
    // api调用接口
    public function getBody($action, $id = 0, array $array = [])
    {
        $response    = [];
        switch ($action)
        {
            // vnc数据
            case 'vnc':
                $response = [
                    'url' => $this->url.'virtual_link_vnc/'.$id.$this->str_sign(),
                ];
                break;
            // 修改密码
            case 'resetPassword':
                $response = $this->getUrl('put', 'virtual_reset_password/'.$id, $array);
                break;
            // 云主机电源
            case 'power':
                $response = $this->getUrl('get', 'virtual_power/'.$id.'/'.$array[0]);
                break;
            // 重装系统
            case 'resetSystem':
                $response = $this->getUrl('get', 'virtual_reset_system/'.$id.'/'.$array[0]);
                break;
            // 创建快照
            case 'resetPassword':
                $response = $this->getUrl('post', 'snapshot/', $array);
                break;
            // 恢复快照
            case 'recoverSnapshot':
                $response = $this->getUrl('get', 'snapshot/'.$id.'/edit');
                break;
            // 修改快照配置
            case 'updateSnapshot':
                $response = $this->getUrl('put', 'snapshot/'.$id, $array);
                break;
            // 删除快照
            case 'deleteSnapshot':
                $response = $this->getUrl('delete', 'snapshot/'.$id);
                break;
            // 删除快照
            case 'deleteVirtual':
                $response = $this->getUrl('delete', 'virtual/'.$id);
                break;
            // 创建云主机
            case 'createVirtual':
                $response = $this->getUrl('post', 'virtual/', $array);
                break;
            // 云服务器监控
            case 'monitor':
                $response = $this->getUrl('get', 'virtual_monitoring/'.$id);
                break;
            // 获取 数据中心
            case 'areas':
                $response = $this->getUrl('get', 'area');
                break;
            // 获取 数据中心 节点
            case 'nodes':
                $response = $this->getUrl('get', 'node/'.$id);
                break;
            // 获取 ip地址池
            case 'ip_pools':
                $response = $this->getUrl('get', 'ip_pool/'.$id);
                break;
            // 获取池IP地址
            case 'ip_addresses':
                $response = $this->getUrl('get', 'ip_address/'.$id);
                break;
            // 获取主控全局系统镜像模版
            case 'mirror_image':
                $response = $this->getUrl('get', 'mirror_image/');
                break;
        }
        return $response;
    }

    // 获取nokvm数据
    public function info()
    {
        // 获取 数据中心
        $array                  = [];
        $array['areas']         = [];
        $array['nodes']         = [];
        $array['ip_pools']      = [];
        $array['ip_addresses']  = [];
        $array['templates']     = [];
        $area   = $this->getBody('areas');
        if ($area['code'] != 0)
        {
            return [
                'code'   =>  1,
                'msg'    =>  '获取数据中心失败！',
            ];
        }
        foreach ($area['data'] as $v)
        {
            $array['areas'][]    = [
                'id'        => $v['id'],
                'name'      => $v['name'],
                'desc'      => $v['desc'],
            ];

            // 获取 数据中心 节点
            $node   = $this->getBody('nodes', $v['id']);
            if ($node['code'] != 0)
            {
                return [
                    'code'   =>  1,
                    'msg'    =>  '获取节点失败！',
                ];
            }

            foreach ($node['data'] as $k)
            {
                $array['nodes'][]  = [
                    'id'        => $k['id'],
                    'name'      => $k['name'],
                    'ip'        => $k['ip'],
                    'status'    => $k['status'],
                    'areas_id'  => $k['areas_id'],
                ];

                // 获取 ip地址池
                $ip     = $this->getBody('ip_pools', $k['id']);
                if ($ip['code'] != 0)
                {
                    return [
                        'code'   =>  1,
                        'msg'    =>  '获取节点ip地址池失败！',
                    ];
                }

                foreach ($ip['data'] as $vv) {

                    if (!$this->deep_in_array($vv['id'], $array['ip_pools']))
                    {
                        $array['ip_pools'][]  = [
                            'id'        => $vv['id'],
                            'gateway'   => $vv['gateway'],
                            'mask'      => $vv['mask'],
                            'type'      => $vv['type'],
                        ];

                        // 获取池IP地址
                        $ip_pools     = $this->getBody('ip_addresses', $vv['id']);
                        if ($ip_pools['code'] != 0)
                        {
                            return [
                                'code'   =>  1,
                                'msg'    =>  '获取节点ip获取池IP地址失败！',
                            ];
                        }
                        foreach ($ip_pools['data'] as $kk)
                        {
                            $array['ip_addresses'][]  = [
                                'id'            => $kk['id'],
                                'ip_pools_id'   => $kk['ip_pools_id'],
                                'ip'            => $kk['ip'],
                                'virtuals_id'   => $kk['virtuals_id'],
                            ];
                        }
                    }
                }
            }
        }

        // 获取主控全局系统镜像模版
        $templates   = $this->getBody('mirror_image');
        if ($templates['code'] != 0)
        {
            return [
                'code'   =>  1,
                'msg'    =>  '获取主控全局系统镜像模版失败！',
            ];
        }
        foreach ($templates['data'] as $v)
        {
            $array['templates'][] =  [
                'id'     => $v['id'],
                'name'   => $v['name'],
                'type'   => $v['type'],
                'desc'   => $v['desc'],
            ];
        }

        return [
            'code'   =>  0,
            'msg'    =>  '您的nokvm主机数据获取成功！通信一切正常！',
            'data'  => $array
        ];
    }

    function deep_in_array($value, $array) {
        foreach($array as $item) {
            if(!is_array($item)) {
                if ($item == $value) {
                    return $item;
                } else {
                    continue;
                }
            }

            if(in_array($value, $item)) {
                return $item;
            } else if($this->deep_in_array($value, $item)) {
                return $item;
            }
        }
        return false;
    }
}