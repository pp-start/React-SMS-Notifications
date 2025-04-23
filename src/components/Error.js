import { Link } from 'react-router-dom';
import ErrorIcon from '../images/error.png';

export default function Error(){

    return (
        <div id="error-outer-container">
            <Link to="/">
                <div id="error-inner-container">
                    <p className="text-error">Sorry, something went wrong <br></br>on our end.</p>
                    <img
                        src={ErrorIcon}
                        alt='error'
                        className="error-image"
                    />
                    <p className="text-error">Push to go back.</p>
                </div>
            </Link>
        </div>
    )
}