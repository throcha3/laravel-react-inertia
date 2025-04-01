import React, { useState } from 'react';
import {router} from "@inertiajs/react";
import {Button} from "reactstrap"; // We need to import this router for making POST request with our form

export default function Create() {
    const [values, setValues] = useState({ // Form fields
        title: "",
        body: ""
    });

    // We will use function below to get
    // values from form inputs
    function handleChange(e) {
        const key = e.target.id;
        const value = e.target.value
        setValues(values => ({
            ...values,
            [key]: value,
        }))
    }

    // This function will send our form data to
    // store function of PostContoller
    function handleSubmit(e) {
        e.preventDefault()
         console.log(values);
         console.log(router);
        // console.log(JSON.stringify(values, null, 2));
        //console.log(M.get().version);
        router.post('/post', values, {
            onSuccess: () => console.log('Success!'),
            onError: (errors) => console.log('Errors:', errors),
        });
    }

    return (
        <>
            <h1>Create Post</h1>
            <hr/>
            <form onSubmit={handleSubmit}>
                {/* Pay attention how we create here input fields */}
                <label htmlFor="title">Title:</label>
                <input id="title" value={values.title} onChange={handleChange} />

                <label htmlFor="body">Body:</label>
                <textarea id="body" value={values.body} onChange={handleChange}></textarea>
                <Button type={"submit"}>Create</Button>
            </form>
        </>
    )
}
