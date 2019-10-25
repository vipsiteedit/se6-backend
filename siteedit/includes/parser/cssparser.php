<?php

function parse_css_selectors($css,$media_queries=true){

    $result = $media_blocks = array();

    //---------------parse css media queries------------------

    if($media_queries==true){

        $media_blocks=parse_css_media_queries($css);
    }

    if(!empty($media_blocks)){

        //---------------get css blocks-----------------

        $css_blocks=$css;

        foreach($media_blocks as $media_block){

            $css_blocks=str_ireplace($media_block,'~£&#'.$media_block.'~£&#',$css_blocks);
        }

        $css_blocks=explode('~£&#',$css_blocks);

        //---------------parse css blocks-----------------

        $b = 0;

        foreach($css_blocks as $css_block){

            preg_match('/(\@media[^\{]+)\{(.*)\}\s+/ims',$css_block,$block);

            if(isset($block[2])&&!empty($block[2])){

                $result[$block[1]]=parse_css_selectors($block[2],false);
            }
            else{

                $result[$b]=parse_css_selectors($css_block,false);
            }

            ++$b;
        }
    } else {

        //---------------escape base64 images------------------

        $css=preg_replace('/(data\:[^;]+);/i','$1~£&#',$css);

        //---------------parse css selectors------------------

        preg_match_all('/([^\{\}]+)\{([^\}]*)\}/ims', $css, $arr);

        foreach ($arr[0] as $i => $x){

            $selector = trim($arr[1][$i]);
            $rules = explode(';', trim($arr[2][$i]));
            $rules_arr = array();
            foreach($rules as $strRule){

                if(!empty($strRule)){
                    $rule = explode(":", $strRule,2);
                    if(isset($rule[1])){
                        $rules_arr[trim($rule[0])] = str_replace('~£&#',';',trim($rule[1]));
                    }
                    else{
                        //debug
                    }
                }
            }

            $selectors = explode(',', trim($selector));
            $b = 0;
            foreach ($selectors as $strSel){

                if($media_queries===true){

                    if (!empty($rules_arr)) {
                        $result[$b][$strSel] = $rules_arr;
                        $b++;
                    }
                }
                else{

                    if (!empty($rules_arr)) {
                        $result[$strSel] = $rules_arr;
                    }
                }
            }
        }
    }
    return $result;
}

function parse_css_media_queries($css){

    $mediaBlocks = array();

    $start = 0;
    while(($start = strpos($css, "@media", $start)) !== false){

        // stack to manage brackets
        $s = array();

        // get the first opening bracket
        $i = strpos($css, "{", $start);

        // if $i is false, then there is probably a css syntax error
        if ($i !== false){

            // push bracket onto stack
            array_push($s, $css[$i]);

            // move past first bracket
            $i++;

            while (!empty($s)){

                // if the character is an opening bracket, push it onto the stack, otherwise pop the stack
                if ($css[$i] == "{"){

                    array_push($s, "{");
                }
                elseif ($css[$i] == "}"){

                    array_pop($s);
                }

                $i++;
            }

            // cut the media block out of the css and store
            $mediaBlocks[] = substr($css, $start, ($i + 1) - $start);

            // set the new $start to the end of the block
            $start = $i;
        }
    }

    return $mediaBlocks;
}