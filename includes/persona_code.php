<?php
    /**
     * Persona integration code
     */

    // Output the Persona powered by text
    echo '<small>Powered by <b><a target="__blank" href="https://withpersona.com/">With Persona</b></a></small><br>&nbsp;<br>';

    // Output the Persona script
?>
<script src="https://cdn.withpersona.com/dist/persona-v5.1.2.js" integrity="sha384-nuMfOsYXMwp5L13VJicJkSs8tObai/UtHEOg3f7tQuFWU5j6LAewJbjbF5ZkfoDo" crossorigin="anonymous"></script>
<script>
	const client = new Persona.Client({
	templateId: 'itmpl_1k5CoM5gd1oo2cbxn1zdZnWZ',
	environmentId: 'env_9tVLdprhMFNx9fHSPZe7HUPM',
	referenceId: '',
	onReady: () => client.open(),
	onComplete: ({ inquiryId, status, fields }) => {
		console.log(`Completed inquiry ${inquiryId} with status ${status}`);
	},
	fields:{
	nameFirst: "Jane",
	nameLast: "Doe",
	birthdate: "2000-12-31",
	addressStreet1: "132 Second St.",
	addressCity: "San Francisco",
	addressSubdivision: "California",
	addressPostalCode: "93441",
	addressCountryCode: "US",
	phoneNumber: "415-415-4154",
	emailAddress: "janedoe@persona.com",
	customAttribute: "hello",
	}
	});
</script>
