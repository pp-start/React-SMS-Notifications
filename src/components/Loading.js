import React from "react"; 
import {ReactComponent as Gear} from '../images/gear.svg';

export default function Loading(){

    return (
        <div id="app-outer-container">
            <div id="app-inner-container">
                <div className="waiting-wrapper">
                    <Gear/>
                </div>
            </div>
        </div>
    );

};