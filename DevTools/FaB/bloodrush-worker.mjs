import {analyze} from './bloodrush-model.mjs?v=2';
self.onmessage=({data:config})=>{
    try {
        const max=config.size-config.bellows-config.blue6Block-config.blue6Nonblock;
        if(max<0)throw Error('Fixed cards exceed deck size');
        for(let reds=0;reds<=max;reds++)self.postMessage({row:analyze(reds,config),done:reds===max,total:max+1});
    } catch(error) {self.postMessage({error:error.message});}
};
